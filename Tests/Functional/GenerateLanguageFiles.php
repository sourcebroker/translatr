<?php

declare(strict_types=1);

// Run from a TYPO3 test installation's root; see README.md.
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\AbstractLogger;
use SourceBroker\Translatr\Database\LabelReader;
use SourceBroker\Translatr\Domain\Exception\LanguageFileGenerationException;
use SourceBroker\Translatr\Middleware\GenerateLanguageFiles as GenerationMiddleware;
use SourceBroker\Translatr\Service\GenerateLanguageFiles;
use SourceBroker\Translatr\Service\GenerationRetry;
use SourceBroker\Translatr\Service\LanguageFilePathResolver;
use SourceBroker\Translatr\Service\Locker;
use SourceBroker\Translatr\Service\OverrideLoaderContentBuilder;
use SourceBroker\Translatr\Service\XlfBuilder;
use TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Service\OpcodeCacheService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

$context = $argv[1] ?? 'Production';
putenv('TYPO3_CONTEXT=' . $context);
$classLoader = require getcwd() . '/vendor/autoload.php';
SystemEnvironmentBuilder::run(0, SystemEnvironmentBuilder::REQUESTTYPE_CLI);
Bootstrap::init($classLoader);

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    echo 'PASS: ' . $message . PHP_EOL;
}

function snapshot(string $directory): array
{
    $files = [];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
        if ($file->isFile()) {
            $files[substr($file->getPathname(), strlen($directory))] = file_get_contents($file->getPathname());
        }
    }
    ksort($files);
    return $files;
}

$directory = sys_get_temp_dir() . '/translatr_generation_' . bin2hex(random_bytes(6));
$originalEnvironment = Environment::toArray();
$composerMode = Environment::isComposerMode();
// Isolate initialize() from the installation's actual generated translations and caches.
Environment::initialize(
    Environment::getContext(),
    true,
    $composerMode,
    Environment::getProjectPath(),
    Environment::getPublicPath(),
    $directory,
    Environment::getConfigPath(),
    Environment::getCurrentScript(),
    $originalEnvironment['os']
);

$locker = new class extends Locker {
    public int $acquires = 0;
    public int $releases = 0;

    public function __construct() {}

    public function acquire(): void
    {
        $this->acquires++;
    }

    public function release(): void
    {
        $this->releases++;
    }
};
$retryBackend = (new Typo3Version())->getMajorVersion() >= 14 ? new TransientMemoryBackend() : new TransientMemoryBackend('Testing');
$retryCache = new VariableFrontend('translatr_test_retry', $retryBackend);
$retry = new GenerationRetry($retryCache);
$generator = new class (
    new LanguageFilePathResolver(),
    new OverrideLoaderContentBuilder(),
    new XlfBuilder(),
    new LabelReader(new ConnectionPool()),
    $locker,
    new OpcodeCacheService(),
    $retry
) extends GenerateLanguageFiles {
    public const SOURCE = 'EXT:translatr_fixture/Resources/Private/Language/locallang.xlf';
    public const TEXT = 'Translation with <markup> & quotes " and apostrophes \' .';
    public string $failure = '';

    public function build(string $publishedDirectory): void
    {
        $this->tempFolderPath = $publishedDirectory;
        $this->generate();
    }

    protected function createLocallangOverrideFiles(): void
    {
        if ($this->failure === 'directory') {
            file_put_contents($this->overrideFilesBaseDirectoryPath . '/ext', 'Blocks directory creation');
        }
        if ($this->failure === 'xlf') {
            $path = (new LanguageFilePathResolver())->getTargetFilePath(self::SOURCE, 'pl', $this->overrideFilesBaseDirectoryPath);
            GeneralUtility::mkdir_deep($path);
        }
        $this->createLocallangOverrideFile(self::SOURCE, ['pl']);
    }

    protected function getLabelsByLocallangFile(string $locallangFile): array
    {
        return [['ukey' => 'sample', 'text' => self::TEXT, 'isocode' => 'pl']];
    }

    protected function createOverrideFilesLoaderFile(): void
    {
        if ($this->failure === 'loader-write') {
            mkdir($this->overrideFilesLoaderFilePath . '.tmp');
        }
        if ($this->failure === 'loader-rename') {
            mkdir($this->overrideFilesLoaderFilePath);
        }
        parent::createOverrideFilesLoaderFile();
    }

    protected function publishStagingFolder(): void
    {
        if ($this->failure === 'publish') {
            GeneralUtility::rmdir($this->stagingFolderPath, true);
        }
        parent::publishStagingFolder();
    }
};

$published = $directory . '/cache/data/tx_translatr';
GeneralUtility::mkdir_deep($published);
file_put_contents($published . '/locallangOverrideLoader.php', '<?php // Previous published loader');
file_put_contents($published . '/previous.xlf', 'Previous published translations');

try {
    check((string)Environment::getContext() === $context, 'Checks run in ' . $context . ' context');
    $before = snapshot($published);
    foreach (['directory', 'xlf', 'loader-write', 'loader-rename', 'publish'] as $failure) {
        $generator->failure = $failure;
        $exception = null;
        try {
            $generator->build($published);
        } catch (Throwable $caught) {
            $exception = $caught;
        }
        check($exception instanceof LanguageFileGenerationException, $failure . ' failure is reported as a recoverable generation error');
        check(snapshot($published) === $before, $failure . ' failure preserves all previously published files');
        check(glob($published . '.*') === [], $failure . ' failure leaves no staging or backup directories');
    }

    $generator->failure = '';
    $generator->build($published);
    check(!file_exists($published . '/previous.xlf'), 'Successful retry replaces the previous output');
    $output = (new LanguageFilePathResolver())->getTargetFilePath($generator::SOURCE, 'pl', $published . '/overrides');
    $xml = new DOMDocument();
    check($xml->load($output) && $xml->getElementsByTagName('target')->item(0)->textContent === $generator::TEXT, 'Published XLF preserves translation text');
    include $published . '/locallangOverrideLoader.php';
    $overrides = (new Typo3Version())->getMajorVersion() >= 14
        ? $GLOBALS['TYPO3_CONF_VARS']['LANG']['resourceOverrides']
        : $GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride'];
    check($overrides['pl'][$generator::SOURCE] === [$output], 'Published loader references the final XLF path');
    check(glob($published . '.*') === [], 'Successful publication removes staging and backup directories');

    GeneralUtility::rmdir($published, true);
    $generator->failure = 'loader-write';
    $failed = false;
    try {
        $generator->initialize();
    } catch (RuntimeException) {
        $failed = true;
    }
    check($failed && $locker->acquires === 1 && $locker->releases === 1, 'Failed initialization reports the error and releases the lock');
    check(!file_exists($published . '/locallangOverrideLoader.php') && glob($published . '.*') === [], 'First generation failure publishes no partial output');
    if ($context === 'Production') {
        $generator->initialize();
        check($locker->acquires === 1, 'Deferred retries do not acquire the generation lock');
    }
    $retry->reset();
    $generator->failure = '';
    $generator->initialize();
    check(is_file($output) && $locker->acquires === 2 && $locker->releases === 2, 'Initialization can retry successfully after a failure');
    $generator->initialize();
    check($locker->acquires === 2, 'Published loader is reused without regeneration');

    $logger = new class extends AbstractLogger {
        public array $records = [];

        public function log($level, string|Stringable $message, array $context = []): void
        {
            $this->records[] = [$level, $message, $context];
        }
    };
    $handler = new class implements RequestHandlerInterface {
        public int $calls = 0;

        public function handle(ServerRequestInterface $request): ResponseInterface
        {
            $this->calls++;
            return new Response();
        }
    };
    $middleware = new GenerationMiddleware($generator);
    $middleware->setLogger($logger);
    GeneralUtility::rmdir($published, true);
    $generator->failure = 'directory';
    $caught = false;
    try {
        $middleware->process(new ServerRequest(), $handler);
    } catch (LanguageFileGenerationException) {
        $caught = true;
    }
    if ($context === 'Production') {
        check(!$caught && $handler->calls === 1 && count($logger->records) === 1, 'Production logs a filesystem failure and continues the frontend request');
        check($logger->records[0][2]['exception'] instanceof LanguageFileGenerationException, 'Production log retains the original exception');
        $acquires = $locker->acquires;
        $middleware->process(new ServerRequest(), $handler);
        check($handler->calls === 2 && count($logger->records) === 1 && $locker->acquires === $acquires, 'Cooldown skips generation and duplicate logging on following requests');
        $retry->reset();
        $generator->failure = '';
        $middleware->process(new ServerRequest(), $handler);
        check(is_file($output) && !$retry->isDeferred(), 'Clearing cooldown allows generation to recover');
        GeneralUtility::rmdir($published, true);
        GeneralUtility::mkdir_deep($published);
    } else {
        check($caught && $handler->calls === 0, 'Development keeps generation failures visible');
    }

    $unexpectedGenerator = new class extends GenerateLanguageFiles {
        public function __construct() {}

        public function initialize(): void
        {
            throw new RuntimeException('Unexpected failure');
        }
    };
    $unexpected = false;
    try {
        (new GenerationMiddleware($unexpectedGenerator))->process(new ServerRequest(), $handler);
    } catch (RuntimeException $exception) {
        $unexpected = $exception->getMessage() === 'Unexpected failure';
    }
    check($unexpected, 'Unexpected runtime errors are not swallowed by the middleware');
    check(!$generator->loadPublishedFiles(), 'Fallback handles missing publication without creating output');
    file_put_contents($published . '/locallangOverrideLoader.php', '<?php $GLOBALS["translatr_fallback_loaded"] = true;');
    check($generator->loadPublishedFiles() && $GLOBALS['translatr_fallback_loaded'] === true, 'Fallback can reuse previously published output');
    unset($GLOBALS['translatr_fallback_loaded']);
} finally {
    GeneralUtility::rmdir($directory, true);
    Environment::initialize(
        Environment::getContext(),
        $originalEnvironment['cli'],
        $composerMode,
        $originalEnvironment['projectPath'],
        $originalEnvironment['publicPath'],
        $originalEnvironment['varPath'],
        $originalEnvironment['configPath'],
        $originalEnvironment['currentScript'],
        $originalEnvironment['os']
    );
}

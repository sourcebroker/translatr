<?php

declare(strict_types=1);

// Run from a bootstrapped TYPO3 test installation's root; see README.md.
use SourceBroker\Translatr\Backend\LabelFilterState;
use SourceBroker\Translatr\Configuration\Configurator;
use SourceBroker\Translatr\Controller\LabelController;
use SourceBroker\Translatr\Database\LabelReader;
use SourceBroker\Translatr\Database\LabelWriter;
use SourceBroker\Translatr\Database\RootPageProvider;
use SourceBroker\Translatr\Domain\Model\Dto\BeLabelDemand;
use SourceBroker\Translatr\Domain\Repository\LabelRepository;
use SourceBroker\Translatr\Service\CacheCleaner;
use SourceBroker\Translatr\Service\LabelIndexer;
use SourceBroker\Translatr\Service\LanguageService;
use SourceBroker\Translatr\Service\SourceLabelParser;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\CommandLineUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Locking\LockFactory;
use TYPO3\CMS\Core\Package\Package;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

$classLoader = require getcwd() . '/vendor/autoload.php';
SystemEnvironmentBuilder::run(0, SystemEnvironmentBuilder::REQUESTTYPE_CLI);
Bootstrap::init($classLoader);
Bootstrap::initializeBackendUser(CommandLineUserAuthentication::class);
$GLOBALS['BE_USER']->authenticate();

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    echo 'PASS: ' . $message . PHP_EOL;
}

function writeLabels(string $file, array $labels): void
{
    $document = new DOMDocument('1.0', 'utf-8');
    $root = $document->appendChild($document->createElement('xliff'));
    $root->setAttribute('version', '1.0');
    $fileNode = $root->appendChild($document->createElement('file'));
    $fileNode->setAttribute('source-language', 'en');
    $body = $fileNode->appendChild($document->createElement('body'));
    foreach ($labels as $key => $text) {
        $unit = $body->appendChild($document->createElement('trans-unit'));
        $unit->setAttribute('id', $key);
        $source = $unit->appendChild($document->createElement('source'));
        $source->appendChild($document->createTextNode($text));
    }
    $document->save($file);
}

$extension = 'translatr_test_' . bin2hex(random_bytes(6));
$directory = sys_get_temp_dir() . '/' . $extension . '/';
GeneralUtility::mkdir_deep($directory . 'Resources/Private/Language/');
$sourceFile = $directory . 'Resources/Private/Language/locallang.xlf';
$backendFile = $directory . 'Resources/Private/Language/locallang_db.xlf';
$relativeFile = 'EXT:' . $extension . '/Resources/Private/Language/locallang.xlf';
$cacheKeys = [
    hash('sha256', 'v1:' . $relativeFile),
    hash('sha256', 'v1:EXT:' . $extension . '/Resources/Private/Language/locallang_db.xlf'),
];

$package = new class ($directory) extends Package {
    public function __construct(private readonly string $directory) {}

    public function getPackagePath(): string
    {
        return $this->directory;
    }
};
$packages = new class ($package) extends PackageManager {
    public function __construct(private readonly Package $package) {}

    public function getPackage($packageKey): Package
    {
        return $this->package;
    }
};
$cacheCleaner = new class extends CacheCleaner {
    public int $flushes = 0;

    public function __construct() {}

    public function flushCache(): void
    {
        $this->flushes++;
    }
};
$pool = GeneralUtility::makeInstance(ConnectionPool::class);
$connection = $pool->getConnectionForTable(LabelRepository::TABLE);
$repository = GeneralUtility::makeInstance(LabelRepository::class);
$persistence = GeneralUtility::makeInstance(PersistenceManager::class);
$languageService = new LanguageService(
    new Configurator(new RootPageProvider($pool), new TypoScriptService()),
    GeneralUtility::makeInstance(LocalizationFactory::class),
    new Typo3Version(),
    new SourceLabelParser(new Typo3Version()),
);
$cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('translatr_index');
$lockFactory = GeneralUtility::makeInstance(LockFactory::class);
$indexer = new LabelIndexer($repository, $languageService, $packages, $persistence, $cache, $lockFactory, $cacheCleaner);
$rows = static fn(): array => $connection->executeQuery(
    'SELECT * FROM ' . LabelRepository::TABLE . ' WHERE extension = ? ORDER BY uid',
    [$extension]
)->fetchAllAssociative();
$find = static function (string $key, string $language = 'default') use ($rows): array {
    foreach ($rows() as $row) {
        if ($row['ukey'] === $key && $row['language'] === $language) {
            return $row;
        }
    }
    throw new RuntimeException('Missing label: ' . $key);
};

$connection->beginTransaction();
try {
    foreach ([['existing', 'default', 0], ['manual', 'default', 1], ['existing', 'pl', 1], ['removed', 'default', 0], ['scheduled', 'default', 0]] as [$key, $language, $modified]) {
        $connection->insert(LabelRepository::TABLE, [
            'pid' => 0, 'extension' => $extension, 'ukey' => $key, 'language' => $language,
            'll_file' => $relativeFile, 'll_file_index' => strrev($relativeFile),
            'text' => 'Original ' . $key . ' ' . $language, 'modify' => $modified,
            'tags' => 'keep,tags', 'description' => 'Keep description',
            'starttime' => $key === 'scheduled' ? time() + 86400 : 0,
        ]);
    }
    $before = $rows();
    $labels = ['existing' => 'From source', 'manual' => 'Do not overwrite', 'new' => 'New label', 'scheduled' => 'Scheduled source'];
    writeLabels($sourceFile, $labels);
    writeLabels($backendFile, ['backend' => 'Backend label']);

    check($indexer->index($extension) === 2, 'Missing cache indexes both files');
    check(count($rows()) === 7, 'Existing and scheduled labels are reused without duplicates');
    check($find('existing')['text'] === 'From source', 'Unmodified source text is synchronized');
    check($find('scheduled')['text'] === 'Scheduled source', 'Scheduled records are found during synchronization');
    check($find('manual') === $before[1], 'Manual label and all metadata are preserved');
    check($find('existing', 'pl') === $before[2], 'Translation is preserved');
    check($find('removed') === $before[3], 'Labels removed from the source remain in the database');
    check($find('existing')['tags'] === 'keep,tags' && $find('existing')['description'] === 'Keep description', 'Tags and description survive updates');
    check($cache->get($cacheKeys[0]) === hash_file('sha256', $sourceFile), 'Successful indexing stores the source hash');

    $snapshot = $rows();
    check($indexer->index($extension) === 0 && $rows() === $snapshot && $cacheCleaner->flushes === 1, 'Unchanged files skip indexing and output cache invalidation');

    $persistence->clearState();
    $labels['existing'] = 'Changed & <fresh> source';
    writeLabels($sourceFile, $labels);
    check($indexer->index($extension) === 1, 'Only the changed file is indexed');
    check($find('existing')['text'] === $labels['existing'], 'Changed file is read without stale parser cache');
    check($find('manual') === $before[1] && $find('existing', 'pl') === $before[2], 'Changed file preserves manual text and translations');

    $connection->update(LabelRepository::TABLE, ['text' => 'External edit'], ['uid' => $find('existing')['uid']]);
    $persistence->clearState();
    check($indexer->index($extension) === 0 && $find('existing')['text'] === 'External edit', 'A cached file is skipped even if an unmodified row was changed externally');
    check($indexer->index($extension, true) === 2 && $find('existing')['text'] === $labels['existing'], 'Forced refresh synchronizes unchanged source files');

    $snapshot = $rows();
    foreach ($cacheKeys as $key) {
        $cache->remove($key);
    }
    $persistence->clearState();
    check($indexer->index($extension) === 2 && $rows() === $snapshot, 'Cache loss reuses existing records without changing their metadata');

    $labels['existing'] = 'Must retry after failure';
    writeLabels($sourceFile, $labels);
    $failingPersistence = new class extends PersistenceManager {
        public function __construct() {}

        public function persistAll(): void
        {
            throw new RuntimeException('Simulated persistence failure');
        }
    };
    $failingIndexer = new LabelIndexer($repository, $languageService, $packages, $failingPersistence, $cache, $lockFactory, $cacheCleaner);
    $failed = false;
    try {
        $failingIndexer->index($extension);
    } catch (RuntimeException $exception) {
        $failed = $exception->getMessage() === 'Simulated persistence failure';
    }
    check($failed && $cache->get($cacheKeys[0]) === false, 'Persistence failure does not publish a successful hash');
    $persistence->clearState();
    check($indexer->index($extension) === 1 && $find('existing')['text'] === $labels['existing'], 'Failed synchronization is retried and the lock is released');

    file_put_contents($sourceFile, '<xliff><broken>');
    $previousXmlErrors = libxml_use_internal_errors(true);
    $failed = false;
    try {
        $indexer->index($extension, true);
    } catch (\TYPO3\CMS\Core\Localization\Exception\InvalidXmlFileException) {
        $failed = true;
    } finally {
        libxml_clear_errors();
        libxml_use_internal_errors($previousXmlErrors);
    }
    check($failed && $cache->get($cacheKeys[0]) === false, 'Malformed XML does not publish a successful hash');
    $controllerConfiguration = new Configurator(new RootPageProvider($pool), new TypoScriptService());
    $controller = new class (
        GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Template\ModuleTemplateFactory::class),
        new LabelReader($pool),
        new LabelFilterState(),
        $indexer,
        new \SourceBroker\Translatr\Service\ExtensionProvider($controllerConfiguration),
        $languageService,
        GeneralUtility::makeInstance(\TYPO3\CMS\Core\FormProtection\FormProtectionFactory::class),
        $controllerConfiguration,
    ) extends LabelController {
        public array $messages = [];

        public function synchronize(string $extension, bool $force = false): bool
        {
            return $this->indexSourceLabels($extension, $force);
        }

        public function addFlashMessage(string $messageBody, string $messageTitle = '', ContextualFeedbackSeverity $severity = ContextualFeedbackSeverity::OK, bool $storeInSession = true): void
        {
            $this->messages[] = [$messageBody, $severity, $storeInSession];
        }
    };
    $snapshot = $rows();
    check(!$controller->synchronize($extension) && $rows() === $snapshot, 'Controller keeps database labels available when source XML is broken');
    check(count($controller->messages) === 1 && $controller->messages[0][1] === ContextualFeedbackSeverity::WARNING
        && str_contains($controller->messages[0][0], $relativeFile) && !$controller->messages[0][2], 'Controller reports the failing source in a current-request warning');
    check(!$controller->synchronize($extension, true), 'Forced synchronization uses the same recoverable XML error handling');
    writeLabels($sourceFile, $labels);
    $persistence->clearState();
    check($indexer->index($extension) === 1, 'Repairing a file retries indexing after a parser failure');

    $reader = new LabelReader($pool);
    $writer = new LabelWriter($pool);
    $demand = new BeLabelDemand();
    $demand->setExtension($extension);
    $demand->setLanguages(['pl']);
    $demand->setKeys(['existing', 'backend']);
    $filtered = $reader->findDemandedForBe($demand, $relativeFile);
    check(count($filtered) === 1 && isset(reset($filtered)['language_childs']['pl']), 'Reader scopes imports to the source file and retains translations');
    check(reset($filtered)['description'] === 'Keep description', 'Reader returns label descriptions');
    check(count($reader->findDemandedForBe($demand)) === 2, 'Backend reader includes matching keys from all source files');
    $writer->updateTranslation((int)$find('existing', 'pl')['uid'], 'Must not overwrite');
    check($find('existing', 'pl') === $before[2], 'Writer refuses to overwrite manually modified translations');
    $writer->updateTranslation((int)$find('existing')['uid'], 'Updated by writer');
    check($find('existing')['text'] === 'Updated by writer', 'Writer updates an unmodified label');
    $writer->updateTags('existing', $extension, $relativeFile, 'updated,tags');
    check($find('existing')['tags'] === 'updated,tags' && $find('existing', 'pl')['tags'] === 'updated,tags'
        && $find('backend')['tags'] === '', 'Tag updates apply to all languages of the selected label only');
    $writer->markModified((int)$find('existing')['uid']);
    check((int)$find('existing')['modify'] === 1, 'Writer marks edited records as modified');
    $writer->delete((int)$find('new')['uid']);
    check(count($rows()) === 6, 'Writer deletes only the requested record');

    // Exercise the real DataHandler command hook on a hard-deleted default record.
    $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
    $dataHandler->start([], [LabelRepository::TABLE => [$find('backend')['uid'] => ['delete' => 1]]]);
    $dataHandler->process_cmdmap();
    check($dataHandler->errorLog === [], 'DataHandler deletes the source record without errors');
    check($cache->get($cacheKeys[1]) === false && $cache->get($cacheKeys[0]) !== false, 'Default deletion invalidates only its source hash');
    $persistence->clearState();
    check($indexer->index($extension) === 1 && $find('backend')['text'] === 'Backend label', 'Opening the index restores a deleted source label');

    $parser = new SourceLabelParser(new Typo3Version());
    $plural = (new Typo3Version())->getMajorVersion() >= 14 ? '{0, plural, one {One} other {Many}}' : 'One';
    $deprecatedKey = (new Typo3Version())->getMajorVersion() >= 14 ? 'old.x-unused' : 'old';
    check($parser->parse('<xliff version="1.2"><file><body><trans-unit id="old" x-unused-since="1.0"><source>Old</source></trans-unit></body></file></xliff>', 'deprecated.xlf') === [$deprecatedKey => 'Old'], 'Deprecated labels retain the runtime-specific key representation');
    check($parser->parse('<xliff xmlns="urn:oasis:names:tc:xliff:document:1.2" version="1.2"><file><body><group><trans-unit id="nested"><source>A &amp; B</source><target>Ignore target</target></trans-unit></group><group restype="x-gettext-plurals" id="count"><trans-unit id="count[0]"><source>One</source></trans-unit><trans-unit id="count[1]"><source>Many</source></trans-unit></group></body></file></xliff>', 'plural.xlf') === ['nested' => 'A & B', 'count' => $plural], 'Independent parser handles namespaces, nested groups, sources and plural forms');
    check($parser->parse('<xliff xmlns="urn:oasis:names:tc:xliff:document:2.0" version="2.0"><file id="f"><unit id="count"><segment><source>One</source></segment><segment><source>Many</source></segment></unit><unit id="space"><segment xml:space="preserve"><source>  kept  </source></segment></unit></file></xliff>', 'v2.xlf') === ['count' => $plural, 'space' => '  kept  '], 'Independent parser supports XLIFF 2 segments and preserved whitespace');
    check($parser->parse('<xliff version="1.2"><file source-language="en"><body><trans-unit id="zero"><source>0</source></trans-unit><trans-unit id="empty"><source/></trans-unit></body></file></xliff>', 'locallang.xml') === ['zero' => '0', 'empty' => ''], 'XLIFF in XML files preserves zero and empty source values');
    foreach (['', '<T3locallang/>', '<!DOCTYPE xliff [<!ENTITY value SYSTEM "file:///etc/passwd">]><xliff/>'] as $invalid) {
        $rejected = false;
        try {
            $parser->parse($invalid, 'invalid.xml');
        } catch (\TYPO3\CMS\Core\Localization\Exception\InvalidXmlFileException) {
            $rejected = true;
        }
        check($rejected, 'Empty, unsupported or entity-bearing XML is rejected explicitly');
    }

    $snapshot = $rows();
    $labels['new'] = 'Must not be queued before all files parse';
    writeLabels($sourceFile, $labels);
    file_put_contents($backendFile, '<xliff><broken>');
    $persistence->clearState();
    try {
        $indexer->index($extension);
        throw new RuntimeException('Expected invalid XML failure');
    } catch (\TYPO3\CMS\Core\Localization\Exception\InvalidXmlFileException) {
    }
    $persistence->persistAll();
    check($rows() === $snapshot, 'A malformed later file leaves no earlier database changes queued');
    writeLabels($backendFile, ['backend' => 'Backend label']);

    $changingParser = new readonly class (new Typo3Version(), $sourceFile) extends SourceLabelParser {
        public function __construct(Typo3Version $version, private string $file)
        {
            parent::__construct($version);
        }

        public function parse(string $contents, string $source): array
        {
            $labels = parent::parse($contents, $source);
            if (str_ends_with($source, '/locallang.xlf')) {
                writeLabels($this->file, ['new' => 'Deployed during indexing']);
            }
            return $labels;
        }
    };
    $changingLanguageService = new LanguageService(
        new Configurator(new RootPageProvider($pool), new TypoScriptService()),
        GeneralUtility::makeInstance(LocalizationFactory::class),
        new Typo3Version(),
        $changingParser,
    );
    $sourceHash = hash_file('sha256', $sourceFile);
    $changingIndexer = new LabelIndexer($repository, $changingLanguageService, $packages, $persistence, $cache, $lockFactory, $cacheCleaner);
    $changingIndexer->index($extension);
    check($cache->get($cacheKeys[0]) === $sourceHash && $find('new')['text'] === $labels['new'], 'Stored hash and labels describe the same snapshot during deployment');
    $persistence->clearState();
    check($indexer->index($extension) === 1 && $find('new')['text'] === 'Deployed during indexing', 'Next indexing detects the concurrent deployment');

    $filterState = new class extends LabelFilterState {
        private BackendUserAuthentication $user;

        public function __construct()
        {
            $this->user = new class extends BackendUserAuthentication {
                private array $moduleValues = [];

                public function pushModuleData(string $module, mixed $data, bool $dontPersistImmediately = false): void
                {
                    $this->moduleValues[$module] = $data;
                }

                public function getModuleData(string $module, string $type = ''): mixed
                {
                    return $this->moduleValues[$module] ?? null;
                }
            };
        }

        protected function getBackendUser(): BackendUserAuthentication
        {
            return $this->user;
        }
    };
    $filterState->remember('news', ['de', 'pl']);
    $resolved = $filterState->resolve(new BeLabelDemand(), ['news' => 'news'], ['pl' => 'Polish', 'de' => 'German']);
    check($resolved->getExtension() === 'news' && $resolved->getLanguages() === ['de', 'pl'], 'Filter state restores existing selections and their order');
    $emptyLanguages = new BeLabelDemand();
    $emptyLanguages->setLanguages([]);
    check($filterState->resolve($emptyLanguages, ['news' => 'news'], ['pl' => 'Polish'])->getLanguages() === [], 'Explicitly clearing languages does not restore a previous selection');
    $filterState->remember('removed_extension', ['pl', 'unknown']);
    $resolved = $filterState->resolve(new BeLabelDemand(), ['news' => 'news'], ['pl' => 'Polish']);
    check($resolved->getExtension() === '' && $resolved->getLanguages() === ['pl'], 'Stale extension and language selections are removed');
} finally {
    $persistence->clearState();
    $connection->rollBack();
    foreach ($cacheKeys as $key) {
        $cache->remove($key);
    }
    GeneralUtility::rmdir($directory, true);
}

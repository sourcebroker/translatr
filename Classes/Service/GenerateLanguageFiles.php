<?php

namespace SourceBroker\Translatr\Service;

use SourceBroker\Translatr\Utility\FileUtility;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use SourceBroker\Translatr\Database\Database;
use SourceBroker\Translatr\Utility\ExceptionUtility;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Locking\Exception\LockAcquireWouldBlockException;
use TYPO3\CMS\Core\Locking\LockFactory;
use TYPO3\CMS\Core\Locking\LockingStrategyInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class GenerateLanguageFiles
{

    protected array $locks = [];
    protected string $overrideFilesLoaderFilePath;
    protected string $overrideFilesBaseDirectoryPath;
    protected string $overrideFilesExtDirectoryPath;


    public function initialize(): void
    {
        $this->setOverrideFilesLoaderFilePath();
        if (!file_exists($this->overrideFilesLoaderFilePath)) {
            $this->acquireLock('tx_translatr', 'tx_translatr_key');
            if (!file_exists($this->overrideFilesLoaderFilePath)) {
                $this->setOverrideFilesBaseDirectoryPath();
                $this->setOverrideFilesExtDirectoryPath();
                $this->createNotExistingLocallangOverrideFiles();
                $this->createOverrideFilesLoaderFileIfNotExists();
                if (!$this->overrideFilesLoaderFileExists()) {
                    ExceptionUtility::throwException(
                        \RuntimeException::class,
                        'Could not create locallang XML Override file in path '
                        . $this->overrideFilesLoaderFilePath . ' due to unknown reason.',
                        82347523
                    );
                    $this->releaseLock('tx_translatr');
                    return;
                }
            }
            $this->releaseLock('tx_translatr');
        }
        include $this->overrideFilesLoaderFilePath;
    }

    protected function setOverrideFilesLoaderFilePath(): void
    {
        $this->overrideFilesLoaderFilePath = FileUtility::getTempFolderPath()
            . '/locallangOverrideLoader.php';
    }

    protected function setOverrideFilesBaseDirectoryPath(): void
    {
        $this->overrideFilesBaseDirectoryPath = FileUtility::getTempFolderPath() . '/overrides';
    }

    protected function setOverrideFilesExtDirectoryPath(): void
    {
        $this->overrideFilesExtDirectoryPath = $this->overrideFilesBaseDirectoryPath . '/ext';
    }

    protected function overrideFilesLoaderFileExists(): bool
    {
        return file_exists($this->overrideFilesLoaderFilePath);
    }

    protected function createOverrideFilesLoaderFileIfNotExists(): void
    {
        if ($this->overrideFilesLoaderFileExists()) {
            return;
        }

        $this->createOverrideFilesLoaderFile();
    }

    protected function createOverrideFilesLoaderFile(): void
    {

        $this->createOverrideFilesLoaderFileDirectoryIfNotExists();
        $this->createOverrideFilesDirectories();
        $code = '<?php' . PHP_EOL;
        foreach ($this->getTranslationOverrideFiles() as $isoCode => $fileDatasets) {
            foreach ($fileDatasets as $fileData) {
                $code .= $this->getFinalOverrideRow($isoCode, $fileData['overwritten'], $fileData['overwriteWith']);
                $code .= $this->getFinalOverrideRow($isoCode,
                    str_replace('EXT:', 'typo3conf/ext/', $fileData['overwritten']), $fileData['overwriteWith']);
            }
        }
        $tempFilename = $this->overrideFilesLoaderFilePath . '.tmp';
        if (!file_put_contents($tempFilename, $code)) {
            ExceptionUtility::throwException(
                \RuntimeException::class,
                'Could not write file in ' . $tempFilename,
                390847534
            );
        }
        GeneralUtility::fixPermissions($tempFilename, true);
        rename($tempFilename, $this->overrideFilesLoaderFilePath);
    }

    protected function getFinalOverrideRow($isoCode, $overwritten, $overwriteWith)
    {
        return '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'locallangXMLOverride\'][\'' . $isoCode . '\'][\''
            . $overwritten . '\'][] = \'' . $overwriteWith . '\';' . PHP_EOL;
    }

    protected function createOverrideFilesLoaderFileDirectoryIfNotExists(): void
    {
        $this->createDirectoryIfNotExists(dirname($this->overrideFilesLoaderFilePath));
    }

    protected function createOverrideFilesDirectories(): void
    {
        $this->createOverrideFilesBaseDirectoryIfNotExists();
        $this->createOverrideFilesExtDirectoryIfNotExists();
    }

    protected function createOverrideFilesBaseDirectoryIfNotExists(): void
    {
        $this->createDirectoryIfNotExists($this->overrideFilesExtDirectoryPath);
    }

    protected function createOverrideFilesExtDirectoryIfNotExists(): void
    {
        $this->createDirectoryIfNotExists($this->overrideFilesExtDirectoryPath);
    }

    protected function createDirectoryIfNotExists(string $directoryPath): void
    {
        if (!is_dir($directoryPath)) {
            GeneralUtility::mkdir_deep($directoryPath);
            if (!is_dir($directoryPath)) {
                ExceptionUtility::throwException(
                    \RuntimeException::class,
                    'Could not create directory in ' . $directoryPath,
                    938457943
                );
            }
        }
    }

    /**
     * @todo check if return of relative path (in element value path) works fine. It will be better to return relative path to avoid problems with some specific server settings
     */
    protected function getTranslationOverrideFiles(): array
    {
        $translationOverrideFiles = [];

        $files = new \RegexIterator(
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->overrideFilesBaseDirectoryPath)
            ),
            '/locallang(|_db)\.xlf|locallang(|_db)\.xml/',
            \RegexIterator::GET_MATCH
        );

        foreach ($files as $fullPath => $file) {
            $isoCode = explode('/', substr($fullPath, strlen($this->overrideFilesBaseDirectoryPath . '/')))[1];
            $translationOverrideFiles[$isoCode][] = [
                'overwritten' => $this->transformPathFromLocallangOverridesToLocallang($fullPath),
                'overwriteWith' => str_replace(Environment::getPublicPath() . '/', '', $fullPath)
            ];
        }
        return $translationOverrideFiles;
    }

    protected function transformPathFromLocallangOverridesToLocallang(string $fullPath): string
    {
        $replacements = [
            $this->overrideFilesExtDirectoryPath => 'EXT:',
            $this->overrideFilesBaseDirectoryPath => '',
        ];
        $pathInfo = pathinfo(explode(':', str_replace(array_keys($replacements), $replacements, $fullPath))[1]);
        $dirnameExploded = explode(DIRECTORY_SEPARATOR, $pathInfo['dirname']);
        array_shift($dirnameExploded);
        array_shift($dirnameExploded);
        $pathNoIso = implode(DIRECTORY_SEPARATOR, $dirnameExploded);

        $nameExploded = explode('.', $pathInfo['basename']);
        array_shift($nameExploded);
        $nameNoIso = implode('.', $nameExploded);

        return 'EXT:' . $pathNoIso . '/' . $nameNoIso;
    }

    protected function transformPathFromLocallangToLocallangOverrides(string $locallangPath, string $isocode): string
    {
        if (\str_starts_with($locallangPath, 'EXT:')) {
            return str_replace('EXT:', $this->overrideFilesExtDirectoryPath . '/' . $isocode . '/', $locallangPath);
        }
        return $this->overrideFilesBaseDirectoryPath . '/' . $locallangPath;
    }

    protected function createNotExistingLocallangOverrideFiles(): void
    {
        if (false === file_exists($this->overrideFilesLoaderFilePath)) {
            $locallangFiles =
                GeneralUtility::makeInstance(Database::class)
                    ->getLocallangFiles();
            if (!$locallangFiles) {
                return;
            }
            foreach ($locallangFiles as $locallangFile) {
                $this->createLocallangOverrideFileIfNotExist($locallangFile['ll_file'], $locallangFile['language']);
            }
        }
    }

    protected function createLocallangOverrideFileIfNotExist(string $locallangFile, string $isoCode): void
    {
        $locallangOverrideFilePath = $this->transformPathFromLocallangToLocallangOverrides($locallangFile, $isoCode);
        if (!is_file($locallangOverrideFilePath)) {
            $this->createLocallangOverrideFile($locallangFile);
        }
    }

    protected function createLocallangOverrideFile(string $locallangFile): void
    {
        $labels = $this->getLabelsByLocallangFile($locallangFile);
        $groupedLabels = [];

        foreach ($labels as $label) {
            $groupedLabels[$label['isocode']][] = $label;
        }

        unset($labels);
        foreach ($groupedLabels as $isoCode => $labels) {
            $xml = $this->createXlfFileForLabels($labels);
            $xml->formatOutput = true;
            $defaultLocallangOverrideFile = $this->transformPathFromLocallangToLocallangOverrides(
                $locallangFile,
                $isoCode
            );
            $outputFiles = [
                $this->prependLocallangFileNameWithIsoCode($defaultLocallangOverrideFile, $isoCode)
            ];
            foreach ($outputFiles as $outputFile) {
                $this->createDirectoryIfNotExists(dirname($outputFile));
                file_put_contents($outputFile, $xml->saveXML());
                $pathParts = pathinfo($outputFile);
                GeneralUtility::fixPermissions($outputFile, true);
                rename($outputFile, $pathParts['dirname'] . '/' . $pathParts['filename'] . '.xlf');
            }
        }
    }

    protected function createXlfFileForLabels(array $labels): \DOMDocument
    {
        $xml = new \DOMDocument('1.0', 'utf-8');
        $root = $xml->createElement('xliff');
        $xml->appendChild($root);
        $root->setAttribute('version', '1.0');

        $file = $xml->createElement('file');
        $root->appendChild($file);
        $file->setAttribute('source-language', 'en');
        $file->setAttribute('datatype', 'plaintext');
        $file->setAttribute('original', 'messages');
        $file->setAttribute('date', (new \DateTime())->format('c'));
        $file->setAttribute('product', ''); // @todo enter $labels[{n}]['extension'] here

        $fileHeader = $xml->createElement('header');
        $file->appendChild($fileHeader);

        $fileBody = $xml->createElement('body');
        $file->appendChild($fileBody);

        foreach ($labels as $label) {
            $transUnit = $xml->createElement('trans-unit');
            $transUnit->setAttribute('id', $label['ukey']);

            if ($label['isocode'] == 'default') {
                $source = $xml->createElement('source');
                $transUnit->appendChild($source);
                $source->appendChild(
                    $xml->createCDATASection($label['text'])
                );
            } else {
                $target = $xml->createElement('target');
                $transUnit->appendChild($target);
                $target->appendChild(
                    $xml->createCDATASection($label['text'])
                );
            }
            $fileBody->appendChild($transUnit);
        }

        return $xml;
    }

    protected function prependLocallangFileNameWithIsoCode(string $filePath, string $isoCode): string
    {
        $fileName = basename($filePath);
        $dirname = dirname($filePath);
        return $dirname . '/' . $isoCode . '.' . $fileName;
    }

    protected function getLabelsByLocallangFile(string $locallangFile): array
    {
        return
            GeneralUtility::makeInstance(Database::class)
                ->getLabelsByLocallangFile($locallangFile);
    }

    protected function acquireLock(string $type, string $key)
    {
        $lockFactory = GeneralUtility::makeInstance(LockFactory::class);
        $this->locks[$type]['accessLock'] = $lockFactory->createLocker($type);

        $this->locks[$type]['pageLock'] = $lockFactory->createLocker(
            $key,
            LockingStrategyInterface::LOCK_CAPABILITY_EXCLUSIVE | LockingStrategyInterface::LOCK_CAPABILITY_NOBLOCK
        );

        do {
            if (!$this->locks[$type]['accessLock']->acquire()) {
                throw new \RuntimeException('Could not acquire access lock for "' . $type . '"".', 1294586098);
            }

            try {
                $locked = $this->locks[$type]['pageLock']->acquire(
                    LockingStrategyInterface::LOCK_CAPABILITY_EXCLUSIVE | LockingStrategyInterface::LOCK_CAPABILITY_NOBLOCK
                );
            } catch (LockAcquireWouldBlockException $e) {
                // somebody else has the lock, we keep waiting

                // first release the access lock
                $this->locks[$type]['accessLock']->release();
                // now lets make a short break (100ms) until we try again, since
                // the page generation by the lock owner will take a while anyways
                usleep(100000);
                continue;
            }
            $this->locks[$type]['accessLock']->release();
            if ($locked) {
                break;
            }
            throw new \RuntimeException('Could not acquire page lock for ' . $key . '.', 1460975877);
        } while (true);
    }

    /**
     * Release a page specific lock
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws NoSuchCacheException
     */
    protected function releaseLock(string $type): void
    {
        if ($this->locks[$type]['accessLock'] ?? false) {
            if (!$this->locks[$type]['accessLock']->acquire()) {
                throw new \RuntimeException('Could not acquire access lock for "' . $type . '"".', 1460975902);
            }

            $this->locks[$type]['pageLock']->release();
            $this->locks[$type]['pageLock']->destroy();
            $this->locks[$type]['pageLock'] = null;

            $this->locks[$type]['accessLock']->release();
            $this->locks[$type]['accessLock'] = null;
        }
    }

}

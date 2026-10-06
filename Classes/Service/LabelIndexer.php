<?php

declare(strict_types=1);

namespace SourceBroker\Translatr\Service;

use SourceBroker\Translatr\Domain\Model\Label;
use SourceBroker\Translatr\Domain\Repository\LabelRepository;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Locking\LockFactory;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

final readonly class LabelIndexer
{
    public function __construct(
        private LabelRepository $labelRepository,
        private LanguageService $languageService,
        private PackageManager $packageManager,
        private PersistenceManagerInterface $persistenceManager,
        private FrontendInterface $indexCache,
        private LockFactory $lockFactory,
        private CacheCleaner $cacheCleaner,
    ) {}

    public function index(string $extensionKey, bool $force = false): int
    {
        $lock = $this->lockFactory->createLocker('translatr-index-' . $extensionKey);
        if (!$lock->acquire()) {
            throw new \RuntimeException('Could not acquire the label indexing lock.', 1791214300);
        }
        try {
            return $this->indexChangedFiles($extensionKey, $force);
        } finally {
            $lock->release();
        }
    }

    public function invalidateSourceFile(string $extensionKey, string $relativeFile): void
    {
        $lock = $this->lockFactory->createLocker('translatr-index-' . $extensionKey);
        if (!$lock->acquire()) {
            throw new \RuntimeException('Could not acquire the label indexing lock.', 1791214300);
        }
        try {
            $this->indexCache->remove(hash('sha256', 'v1:' . $relativeFile));
        } finally {
            $lock->release();
        }
    }

    private function indexChangedFiles(string $extensionKey, bool $force): int
    {
        $packagePath = $this->packageManager->getPackage($extensionKey)->getPackagePath();
        $languageDirectory = $packagePath . 'Resources/Private/Language/';
        $files = array_merge(
            glob($languageDirectory . 'locallang.{xml,xlf}', GLOB_BRACE) ?: [],
            glob($languageDirectory . 'locallang_db.{xml,xlf}', GLOB_BRACE) ?: [],
        );

        $indexedHashes = [];
        $preparedLabels = [];
        foreach ($files as $file) {
            $relativeFile = 'EXT:' . $extensionKey . '/' . ltrim(substr($file, strlen($packagePath)), '/');
            $cacheKey = hash('sha256', 'v1:' . $relativeFile);
            $contents = @file_get_contents($file);
            if ($contents === false) {
                throw new \RuntimeException('Could not read language file: ' . $relativeFile, 1791214301);
            }
            $fileHash = hash('sha256', $contents);
            if (!$force && $this->indexCache->get($cacheKey) === $fileHash) {
                continue;
            }
            // A failed forced refresh must be retried on the next automatic run as well.
            $this->indexCache->remove($cacheKey);
            $preparedLabels[$relativeFile] = $this->languageService->parseSourceLabels($contents, $relativeFile);
            $indexedHashes[$cacheKey] = $fileHash;
        }

        if ($indexedHashes !== []) {
            // Parse every changed source before scheduling any database changes.
            foreach ($preparedLabels as $relativeFile => $labels) {
                $this->indexFile($extensionKey, $relativeFile, $labels);
            }
            $this->persistenceManager->persistAll();
            $this->cacheCleaner->flushCache();
            foreach ($indexedHashes as $cacheKey => $fileHash) {
                $this->indexCache->set($cacheKey, $fileHash, [], 0);
            }
        }

        return count($indexedHashes);
    }

    /** @param array<string, string> $labels */
    private function indexFile(string $extensionKey, string $relativeFile, array $labels): void
    {
        foreach ($labels as $labelKey => $text) {
            if ($text === '') {
                continue;
            }

            $label = $this->createLabel($extensionKey, (string)$labelKey, $text, $relativeFile);
            $indexedLabel = $this->labelRepository->findIndexedLabel($label);
            if ($indexedLabel instanceof Label) {
                if (!$indexedLabel->getModify() && $indexedLabel->getText() !== $text) {
                    $indexedLabel->setText($label->getText());
                    $this->labelRepository->update($indexedLabel);
                }
                continue;
            }
            $this->labelRepository->add($label);
        }
    }

    private function createLabel(string $extensionKey, string $key, string $text, string $file): Label
    {
        $label = new Label();
        $label->setExtension($extensionKey);
        $label->setPid(0);
        $label->setText($text);
        $label->setUkey($key);
        $label->setLlFile($file);
        $label->setLlFileIndex(strrev($file));
        $label->setLanguage('default');
        $label->setModify(0);

        return $label;
    }
}

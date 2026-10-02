<?php

namespace SourceBroker\Translatr\Domain\Repository;

use SourceBroker\Translatr\Configuration\Configurator;
use SourceBroker\Translatr\Database\Database;
use SourceBroker\Translatr\Domain\Model\Dto\BeLabelDemand;
use SourceBroker\Translatr\Domain\Model\Label;
use SourceBroker\Translatr\Utility\FileUtility;
use SourceBroker\Translatr\Utility\LanguageUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Label>
 */
class LabelRepository extends Repository
{
    public const TABLE = 'tx_translatr_domain_model_label';

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findDemandedForBe(BeLabelDemand $demand): array
    {
        return
            GeneralUtility::makeInstance(Database::class)
                ->findDemandedForBe($demand);
    }

    /**
     * @return array<string, string>
     */
    public function getExtensionsItems(): array
    {
        $config = GeneralUtility::makeInstance(Configurator::class);
        $extensions = array_intersect(
            (array)$config->getOption('extensions'),
            ExtensionManagementUtility::getLoadedExtensionListArray()
        );
        sort($extensions);
        return array_combine($extensions, $extensions);
    }

    /**
     * @todo When support for more files will be implemented, then indexing
     *       proces should be moved somewhere else to speed up the BE module
     *       (currently it's done on every request to keep labels up to date)
     *
     * @todo Implement support for other translation files as currently only
     *       the main FE translation file is supported
     *       (EXT:{extKey}/Resources/Private/Language/locallang.xlf or
     *       EXT:{extKey}/Resources/Private/Language/locallang.xml)
     */
    public function indexExtensionLabels(string $extKey): void
    {
        $llDirectoryPath = ExtensionManagementUtility::extPath($extKey) . 'Resources/Private/Language/';
        $llFilesFrontend = glob($llDirectoryPath . 'locallang.{xml,xlf}', GLOB_BRACE) ?: [];
        $llFilesBackend = glob($llDirectoryPath . 'locallang_db.{xml,xlf}', GLOB_BRACE) ?: [];
        $llFiles = array_merge($llFilesFrontend, $llFilesBackend);

        if (!isset($llFiles[0]) || !file_exists($llFiles[0])) {
            return;
        }
        foreach ($llFiles as $llFile) {
            $parsedLabels = LanguageUtility::parseLanguageLabels($llFile, 'default');
            $labels = [];

            if (!isset($parsedLabels['default'])) {
                return;
            }

            foreach ($parsedLabels['default'] as $labelKey => $labelData) {
                $labels[$labelKey] = $labelData[0]['target']
                    ?: $labelData[0]['source'] ?: null;
            }

            // remove null labels
            $labels = array_filter($labels, fn($label) => !is_null($label));

            foreach ($labels as $labelKey => $label) {
                $obj = new Label();
                $obj->setExtension($extKey);
                $obj->setPid(0);
                $obj->setText($label);
                $obj->setUkey($labelKey);
                $obj->setLlFile(FileUtility::getRelativePathFromAbsolute($llFile, $extKey));
                $obj->setLlFileIndex(strrev(FileUtility::getRelativePathFromAbsolute($llFile, $extKey)));
                $obj->setLanguage('default');
                $obj->setModify(0);
                $indexedLabel = $this->getIndexedLabel($obj);
                try {
                    if ($indexedLabel instanceof Label) {
                        if (!$indexedLabel->getModify()) {
                            $indexedLabel->setText($obj->getText());
                            $this->update($indexedLabel);
                        }
                    } else {
                        $this->add($obj);
                    }
                } catch (IllegalObjectTypeException|UnknownObjectException) {
                }
                unset($obj);
            }
            GeneralUtility::makeInstance(PersistenceManager::class)->persistAll();
        }
    }

    protected function getIndexedLabel(Label $label): ?Label
    {
        $query = $this->createQuery();

        $query->getQuerySettings()->setEnableFieldsToBeIgnored([
            'starttime',
            'endtime',
        ]);

        return $query->matching($query->logicalAnd(
            $query->equals('language', $label->getLanguage()),
            $query->equals('llFile', $label->getLlFile()),
            $query->equals('ukey', $label->getUkey()),
        ))->execute()->getFirst();
    }

    /**
     * @param array<string, mixed> $values
     */
    public function updateSelectedRowInAllLanguages(string $key, string $extension, string $path, array $values): void
    {
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(self::TABLE)
            ->update(
                self::TABLE,
                $values,
                [
                    'extension' => $extension,
                    'ukey' => $key,
                    'll_file' => $path,
                ]
            );
    }

    /**
     * @param array<string, mixed> $values
     */
    public function updateSelectedRow(int $uid, array $values): void
    {
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(self::TABLE)
            ->update(
                self::TABLE,
                $values,
                [
                    'uid' => $uid,
                ]
            );
    }

    /**
     * @param array<string, mixed> $defaultLabel
     */
    public function createLanguageChildFromDefault(
        array $defaultLabel,
        string $translationFromFile,
        string $language
    ): void {
        /** @var Label $label */
        $label = GeneralUtility::makeInstance(Label::class);
        $label->setPid(0);
        $label->setExtension($defaultLabel['extension']);
        $label->setText($translationFromFile);
        $label->setUkey($defaultLabel['ukey']);
        $label->setLlFile($defaultLabel['ll_file']);
        $label->setLlFileIndex(strrev($defaultLabel['ll_file']));
        $label->setLanguage($language);
        $label->setModify(0);
        $label->setTags($defaultLabel['tags']);
        try {
            $this->add($label);
        } catch (IllegalObjectTypeException) {
        }
    }
}

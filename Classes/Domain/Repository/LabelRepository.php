<?php

namespace SourceBroker\Translatr\Domain\Repository;

use SourceBroker\Translatr\Domain\Model\Dto\BeLabelDemand;
use SourceBroker\Translatr\Domain\Model\Label;
use SourceBroker\Translatr\Utility\ExtensionsUtility;
use SourceBroker\Translatr\Utility\FileUtility;
use SourceBroker\Translatr\Utility\LanguageUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2015
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * The repository for Labels
 */
class LabelRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{

    /**
     * @param BeLabelDemand $demand
     *
     * @return array
     */
    public function findDemandedForBe(BeLabelDemand $demand)
    {
        return
            GeneralUtility::makeInstance($GLOBALS['TYPO3_CONF_VARS']['EXT']['EXTCONF']['translatr']['database'])
                ->findDemandedForBe($demand);
    }

    /**
     * @return array
     */
    public function getExtensionsItems()
    {
        $extensions = [''];
        foreach (ExtensionsUtility::getExtensionsWithMetaData() as $extData) {
            $extensions[$extData['extensionKey']] = $extData['extensionKey'] . ' (' . ($extData['title']) . ')';
        }
        ksort($extensions);
        return $extensions;
    }

    /**
     * @param string $extKey
     *
     * @return void
     * @todo Implement support for other translation files as currently only
     *       the main FE translation file is supported
     *       (EXT:{extKey}/Resources/Private/Language/locallang.xlf or
     *       EXT:{extKey}/Resources/Private/Language/locallang.xml)
     * @todo When support for more files will be implemented, then indexing
     *       proces should be moved somewhere else to speed up the BE module
     *       (currently it's done on every request to keep labels up to date)
     *
     */
    public function indexExtensionLabels($extKey)
    {
        $llDirectoryPath = PATH_site . 'typo3conf/ext/' . $extKey . '/Resources/Private/Language/';
        $llFilesFrontend = glob($llDirectoryPath . 'locallang.{xml,xlf}', GLOB_BRACE);
        $llFilesBackend = glob($llDirectoryPath . 'locallang_db.{xml,xlf}', GLOB_BRACE);
        $llFiles = array_merge($llFilesFrontend, $llFilesBackend);

        if (!is_array($llFiles) || !isset($llFiles[0])
            || !file_exists($llFiles[0])
        ) {
            return;
        }
        foreach ($llFiles as $llFile) {
            $parsedLabels = LanguageUtility::parseLanguageLabels($llFile, 'default');
            $labels = [];

            if (!is_array($parsedLabels) || !isset($parsedLabels['default'])
                || !is_array($parsedLabels['default'])
            ) {
                return;
            }

            foreach ($parsedLabels['default'] as $labelKey => $labelData) {
                $labels[$labelKey] = $labelData[0]['target']
                    ?: $labelData[0]['source'] ?: null;
            }

            // remove null labels
            $labels = array_filter($labels, function ($label) {
                return !is_null($label);
            });

            foreach ($labels as $labelKey => $label) {
                $obj = new Label();
                $obj->setExtension($extKey);
                $obj->setText($label);
                $obj->setUkey($labelKey);
                $obj->setLlFile(FileUtility::getRelativePathFromAbsolute($llFile));
                $obj->setLlFileIndex(strrev(FileUtility::getRelativePathFromAbsolute($llFile)));
                $obj->setLanguage('default');
                if (!$this->isLabelIndexed($obj)) {
                    $this->add($obj);
                }
                unset($obj);
            }
            $this->objectManager->get(PersistenceManager::class)->persistAll();
        }
    }

    /**
     * @param Label $label
     *
     * @return bool
     */
    protected function isLabelIndexed(Label $label)
    {
        $query = $this->createQuery();

        $query->getQuerySettings()->setEnableFieldsToBeIgnored([
            'starttime',
            'endtime',
        ]);

        return $query->matching(
                $query->logicalAnd([
                    $query->equals('language', $label->getLanguage()),
                    $query->equals('llFile', $label->getLlFile()),
                    $query->equals('ukey', $label->getUkey()),
                ])
            )->count() > 0;
    }

    /**
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    public function findByProperties(string $extension, string $path, string $key)
    {
        $query = $this->createQuery();

        $query->getQuerySettings()->setEnableFieldsToBeIgnored([
            'starttime',
            'endtime',
        ]);

        return $query->matching(
            $query->logicalAnd([
                $query->equals('extension', $extension),
                $query->equals('llFile', $path),
                $query->equals('ukey', $key),
            ])
        )->execute();
    }
}

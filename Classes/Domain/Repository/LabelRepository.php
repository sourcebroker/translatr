<?php

namespace SourceBroker\Translatr\Domain\Repository;

use SourceBroker\Translatr\Domain\Model\Dto\BeLabelDemand;
use SourceBroker\Translatr\Domain\Model\Label;
use SourceBroker\Translatr\Utility\ArrayUtility;
use SourceBroker\Translatr\Utility\ExtensionsUtility;
use SourceBroker\Translatr\Utility\FileUtility;
use SourceBroker\Translatr\Utility\LanguageUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;
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
        if (!$demand->isValid()) {
            return [];
        }

        $extensionNameForSql = self::getDb()->fullQuoteStr(
            $demand->getExtension(),
            'tx_translatr_domain_model_label'
        );

        $languageListForSql = implode(
            ', ',
            self::getDb()->fullQuoteArray($demand->getLanguages() ?: ['default'], 'tx_translatr_domain_model_label')
        );

        $query = <<<SQL
/* select labels from default language */
(
SELECT 
  label.uid,
  label.language,
  label.ukey,
  0 AS parent_uid,
  label.text,
  label.ll_file
FROM tx_translatr_domain_model_label AS label
WHERE label.language = "default" 
  AND label.deleted = 0
  AND label.extension = {$extensionNameForSql}
) UNION (
/* select labels for specified languages */ 
SELECT  
  label.uid,
  label.language,
  label.ukey,
  parent.uid AS parent_uid,
  label.text,
  label.ll_file
FROM tx_translatr_domain_model_label AS label 
  LEFT JOIN tx_translatr_domain_model_label AS parent
    ON (parent.language = "default" AND parent.ukey = label.ukey AND parent.ll_file = label.ll_file)
WHERE label.language IN ({$languageListForSql})  
  AND label.deleted = 0
  AND parent.deleted = 0
  AND parent.extension = {$extensionNameForSql}
);
SQL;

        // sql_query()->fetch_all() is still not supported on all hostings
        $result = self::getDb()->sql_query($query);
        $resultAssoc = [];
        while ($row = $result->fetch_assoc()) {
            $resultAssoc[] = $row;
        }
        $results = ArrayUtility::combineWithSubarrayFieldAsKey(
            $resultAssoc,
            'uid'
        );

        $processedResults = [];

        foreach ($results as &$result) {
            $uid = (int)$result['uid'];
            $parentUid = (int)$result['parent_uid'];
            $language = $result['language'];

            if ($language === 'default') {
                // record in default language are treated as parents
                $processedResults[$uid] = $result;
                $processedResults[$uid]['language_childs'] = [];
            } elseif ($parentUid > 0) {
                // add as a child to parent record
                $processedResults[$parentUid]['language_childs'][$language]
                    = $result;
            }
        }

        return $processedResults;
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
     * @todo Implement support for other translation files as currently only
     *       the main FE translation file is supported
     *       (EXT:{extKey}/Resources/Private/Language/locallang.xlf or
     *       EXT:{extKey}/Resources/Private/Language/locallang.xml)
     * @todo When support for more files will be implemented, then indexing
     *       proces should be moved somewhere else to speed up the BE module
     *       (currently it's done on every request to keep labels up to date)
     *
     * @param string $extKey
     *
     * @return void
     */
    public function indexExtensionLabels($extKey)
    {
        $llDirectoryPath = PATH_site.'typo3conf'.DIRECTORY_SEPARATOR.'ext'
            .DIRECTORY_SEPARATOR.$extKey.DIRECTORY_SEPARATOR.'Resources'
            .DIRECTORY_SEPARATOR.'Private'.DIRECTORY_SEPARATOR.'Language'
            .DIRECTORY_SEPARATOR;
        $llFiles = glob($llDirectoryPath.'locallang.{xlf,xml}', GLOB_BRACE);

        if (!is_array($llFiles) || !isset($llFiles[0])
            || !file_exists($llFiles[0])
        ) {
            return;
        }

        $llFilePath = $llFiles[0];

        $parsedLabels = LanguageUtility::parseLanguageLabels($llFilePath, 'default');
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
            $obj->setPid(0);
            $obj->setExtension($extKey);
            $obj->setText($label);
            $obj->setUkey($labelKey);
            $obj->setLlFile(FileUtility::getRelativePathFromAbsolute($llFilePath));
            $obj->setLanguage('default');

            if (!$this->isLabelIndexed($obj)) {
                $this->add($obj);
            }

            unset($obj);
        }

        $this->objectManager->get(PersistenceManager::class)->persistAll();
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

    /**
     * @return DatabaseConnection
     */
    protected function getDb()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
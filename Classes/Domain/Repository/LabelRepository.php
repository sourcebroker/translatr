<?php

namespace SourceBroker\Translatr\Domain\Repository;

use SourceBroker\Translatr\Domain\Model\Dto\BeLabelDemand;
use SourceBroker\Translatr\Utility\ExtensionsUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;

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
     * @todo Implement real fallback used in FE (include all settings from sys_language_mode
     *     https://docs.typo3.org/typo3cms/TyposcriptReference/Setup/Config/Index.html#sys-language-mode)
     *
     * @param BeLabelDemand $demand
     *
     * @return array
     */
    public function findDemandedForBe(BeLabelDemand $demand)
    {
        if (!$demand->isValid()) {
            return [];
        }

        // NOTICE! Seems that extbase language fallback is not working correctly in BE, so manual fallback has to be done here
        $query = <<<SQL
/* select labels from default language */
(
SELECT *
FROM tx_translatr_domain_model_label
WHERE tx_translatr_domain_model_label.sys_language_uid = 0 
  AND tx_translatr_domain_model_label.deleted = 0
  AND tx_translatr_domain_model_label.extension = "{$demand->getExtension()}"
) UNION (
/* select labels for all languages */
SELECT *
FROM tx_translatr_domain_model_label
WHERE tx_translatr_domain_model_label.sys_language_uid = -1 
  AND tx_translatr_domain_model_label.deleted = 0
  AND tx_translatr_domain_model_label.extension = "{$demand->getExtension()}"
) UNION (
/* select labels for specified language */ 
SELECT tx_translatr_domain_model_label.* 
FROM tx_translatr_domain_model_label 
  LEFT JOIN tx_translatr_domain_model_label AS parent
    ON (tx_translatr_domain_model_label.l10n_parent = parent.uid)
WHERE tx_translatr_domain_model_label.sys_language_uid = {$demand->getSysLanguageUid()} 
  AND tx_translatr_domain_model_label.deleted = 0
  AND parent.deleted = 0
  AND parent.extension = "{$demand->getExtension()}"
);
SQL;

        $query = self::getDb()->sql_query($query);
        $rows = [];

        while ($result = $query->fetch_assoc()) {
            if (empty($result['l10n_parent'])) {
                // is parent record
                $rows[$result['uid']] = $result;
            } elseif (isset($rows[$result['l10n_parent']])) {
                $parentRecord =& $rows[$result['l10n_parent']];

                // parent record exists, overwrite it fields
                foreach ($result as $columnName => $columnValue) {
                    $l10nMode = isset($GLOBALS['TCA']['tx_translatr_domain_model_label']['columns'][$columnName]['l10n_mode']) ?
                        $GLOBALS['TCA']['tx_translatr_domain_model_label']['columns'][$columnName]['l10n_mode'] : null;

                    switch ($l10nMode) {
                        case 'mergeIfNotBlank':
                            // overwrite parent record only if field is not empty in translation
                            if (!empty($columnValue)) {
                                $parentRecord[$columnName] = $columnValue;
                            }
                            break;
                        case 'exclude':
                            // do no overwrite parent record at all
                            break;
                        default:
                            // overwrite parent record value
                            $parentRecord[$columnName] = $columnValue;

                    }
                }
            }
        }

        return $rows;
    }

    /**
     * @return array
     */
    public function getExtensionsItems()
    {
        $extensions = [''];
        foreach (ExtensionsUtility::getExtensionsListForTranslate() as $extensionData) {
            if (isset($extensionData[1]) && $extensionData[1]) {
                $extensions[$extensionData[1]] = $extensionData[0];
            }
        }

        return $extensions;
    }

    /**
     * @return array
     */
    public function getSysLanguagesItems()
    {
        $languages = [
            -1 => 'All',
            0 => 'Default'
            /* @todo get default language title here */
        ];

        foreach (array_filter(
            (array)$this->getDb()->exec_SELECTgetRows('uid, title', 'sys_language', '1 = 1')
        ) as $lang) {
            $languages[(int)$lang['uid']] = $lang['title'];
        }

        return $languages;
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
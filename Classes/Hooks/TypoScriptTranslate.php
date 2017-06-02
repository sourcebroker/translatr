<?php

namespace SourceBroker\Translatr\Hooks;

use SourceBroker\Translatr\Utility\EmConfiguration;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\TypoScriptService;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Class TypoScriptTranslate
 *
 * @package SourceBroker\Translatr\Hooks
 */
class TypoScriptTranslate
{
    /**
     * Keep the status of translations to avoid executing multiple times
     *
     * @var bool
     */
    public static $STATUS = false;

    /**
     * @todo implement support for `config.sys_language_mode` fallback order.
     *       Currently default langauge is treated as fallback
     *
     * @param array                                      $params
     * @param \TYPO3\CMS\Core\TypoScript\TemplateService $templateService
     *
     * @return void
     */
    public function loadTranslations($params, &$templateService)
    {
        if (self::areTranslationsAlreadyLoaded() || 'FE' !== TYPO3_MODE) {
            return;
        }

        $translationArray = [];
        $extTranslations = $this->getExtTranslations();
        $languageMapping = self::getLanguageKeysMapping();

        foreach ($extTranslations as $extKey => $extLangTranslations) {
            // @todo somewhere here support for fallback order from `config.sys_language_mode` should be implemented
            // set labels from default language (sys_language_uid === 0) as fallback for all other languages
            $this->setLabelForAllLanguages($extKey, $extLangTranslations, 0,
                $languageMapping, $translationArray);
            // set labels from `All languages` (sys_language_uid == -1) as fallback for all other languages
            $this->setLabelForAllLanguages($extKey, $extLangTranslations, -1,
                $languageMapping, $translationArray);

            // remove labels for `All languages`, because they are already supported above
            unset($extLangTranslations[0], $extLangTranslations[-1]);

            foreach ($extLangTranslations as $languageUid => $labels) {
                if ($languageIso = $languageMapping[$languageUid]) {
                    foreach ($labels as $labelKey => $label) {
                        $translationArray['extension']['tx_'
                        .$extKey]['_LOCAL_LANG'][$languageIso][$labelKey]
                            = $label;
                    }
                }
            }
        }

        $tsArr = self::getTsService()
            ->convertPlainArrayToTypoScriptArray($translationArray);

        $templateService->setup
            = array_replace_recursive((array)$templateService->setup, $tsArr);

        self::$STATUS = true;
    }

    /**
     * @param string $extKey              Key of the extension
     * @param array  $extLangTranslations Multidimensional array where keys of
     *                                    the first level are languages uids
     *                                    and keys of the second level are
     *                                    labels keys
     * @param int    $sourceLanguageUid   ID of the language which should be
     *                                    set for all languages
     * @param array  $languageMapping     Language mapping array, where keys
     *                                    are languages UIDs and values are
     *                                    languages ISO codes
     * @param array  $translationArray    Output array with translations passed
     *                                    as reference
     */
    protected function setLabelForAllLanguages(
        $extKey,
        array $extLangTranslations,
        $sourceLanguageUid,
        array $languageMapping,
        array &$translationArray
    ) {
        if (isset($extLangTranslations[$sourceLanguageUid])) {
            foreach (
                $extLangTranslations[$sourceLanguageUid] as $labelKey => $label
            ) {
                $translationArray['extension']['tx_'
                .$extKey]['_LOCAL_LANG']['default'][$labelKey]
                    = $label;

                foreach ($languageMapping as $languageUid => $languageIso) {
                    if ($languageIso) {
                        $translationArray['extension']['tx_'
                        .$extKey]['_LOCAL_LANG'][$languageIso][$labelKey]
                            = $label;
                    }
                }
            }
        }
    }

    /**
     * @return array
     */
    protected function getExtTranslations()
    {
        $translations = [];

        foreach (self::getAllLabels() as $label) {
            $translations[$label['uid']] = $label;
        }

        $extTranslations = [];

        foreach ($translations as $translation) {
            // get extension from parent record
            if (
                empty($translation['extension'])
                && !empty($translation['l10n_parent'])
                && isset($translations[$translation['l10n_parent']])
            ) {
                $extension = GeneralUtility::trimExplode(',',
                    $translations[$translation['l10n_parent']]['extension']);
            } else {
                $extension = GeneralUtility::trimExplode(',',
                    $translation['extension']);
            }

            // get label key from parent record
            if (
                empty($translation['ukey'])
                && !empty($translation['l10n_parent'])
                && isset($translations[$translation['l10n_parent']])
            ) {
                $key = $translations[$translation['l10n_parent']]['ukey'];
            } else {
                $key = $translation['ukey'];
            }

            foreach ($extension as $ext) {
                $extTranslations[$ext][$translation['sys_language_uid']][$key]
                    = $translation['text'];
            }
        }

        return $extTranslations;
    }

    /**
     * @return bool
     */
    protected static function areTranslationsAlreadyLoaded()
    {
        return self::$STATUS;
    }

    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected static function getDb()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * @return \TYPO3\CMS\Frontend\Page\PageRepository
     */
    protected static function getPageRepository()
    {
        return self::getTsfe()->sys_page;
    }

    /**
     * @return array
     */
    protected static function getLanguageKeysMapping()
    {
        $sysLanguages = self::getSysLanguageRecords();

        if (!$sysLanguages) {
            $sysLanguages = [];
        }

        /** @todo Resolve problem with chinese language (ch <=> cn) */

        return array_merge([self::getDefaultLanguageIsoCode()],
            array_map(function ($sysLanguage) {
                return $sysLanguage['isocode'];
            }, $sysLanguages));
    }

    /**
     * @return array
     */
    protected static function getAllLabels()
    {
        return array_filter((array)self::getDb()->exec_SELECTgetRows(
            'uid, extension, ukey, text, sys_language_uid, l10n_parent',
            'tx_translatr_domain_model_label ',
            '1 = 1 '.self::getPageRepository()
                ->enableFields('tx_translatr_domain_model_label')
        ));
    }

    /**
     * @return TypoScriptService
     */
    protected static function getTsService()
    {
        return GeneralUtility::makeInstance(TypoScriptService::class);
    }

    /**
     * @return TypoScriptFrontendController
     */
    protected static function getTsfe()
    {
        return $GLOBALS['TSFE'];
    }

    /**
     * Result include additional `isocode` column, which includes isocode of
     * the language. If `static_info_tables` extension is loaded, then
     * `static_languages.lg_typo3` column is used for it, otherwise
     * `sys_language.flag` column is used.
     *
     * @todo adjust if for TYPO3 7.6. Probably there column
     *       `sys_language.language_isocode` can be used to get isocode of
     *       langauge.
     *
     * @return array|NULL
     */
    protected static function getSysLanguageRecords()
    {
        if (ExtensionManagementUtility::isLoaded('static_info_tables')) {
            return self::getDb()->exec_SELECTgetRows(
                'sys_language.*, IFNULL(static_languages.lg_typo3, sys_language.flag) AS isocode',
                'sys_language LEFT JOIN static_languages ON (sys_language.static_lang_isocode = static_languages.uid)',
                '1 = 1'
            );
        } else {
            return self::getDb()->exec_SELECTgetRows(
                '*, flag AS isocode',
                'sys_language',
                '1 = 1'
            );
        }
    }

    /**
     * @return string
     */
    protected static function getDefaultLanguageIsoCode()
    {
        return EmConfiguration::getSettings()->getDefaultLanguageIsoCode();
    }
}
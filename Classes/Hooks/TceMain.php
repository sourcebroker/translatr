<?php

namespace SourceBroker\Translatr\Hooks;

use SourceBroker\Translatr\Domain\Model\Dto\EmConfiguration;
use SourceBroker\Translatr\Utility\EmConfigurationUtility;
use SourceBroker\Translatr\Utility\ExceptionUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

/**
 * Class TceMain
 *
 * @package SourceBroker\Translatr\Hooks
 */
class TceMain
{
    /**
     * @param $command
     * @param $table
     * @param $id
     * @param $value
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $pObj
     */
    public function processCmdmap_postProcess(
        $command,
        $table,
        $id,
        $value,
        \TYPO3\CMS\Core\DataHandling\DataHandler &$pObj
    ) {
        if ($table == 'tx_translatr_domain_model_label' && $command == 'delete') {
            $record = BackendUtility::getRecord($table, $id);
            $this->clearCacheForLanguage($record['language']);
            \SourceBroker\Translatr\Utility\FileUtility::getTempFolderPath();
        }
    }

    /**
     * @param $status
     * @param $table
     * @param $id
     * @param array $fieldArray
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $pObj
     */
    public function processDatamap_afterDatabaseOperations(
        $status,
        $table,
        $id,
        array $fieldArray,
        \TYPO3\CMS\Core\DataHandling\DataHandler &$pObj
    ) {
        if ($table == 'tx_translatr_domain_model_label') {
            $record = BackendUtility::getRecord($table, $id);
            $this->clearCacheForLanguage($record['language']);
        }
    }

    /**
     *  Make atomic remove.

     * @param $language
     */
    private function clearCacheForLanguage($language)
    {
        // TODO: clear only for language and not for all
        $tempPath = \SourceBroker\Translatr\Utility\FileUtility::getTempFolderPath();
        $tempPathRenamed = $tempPath . time();
        rename($tempPath, $tempPathRenamed);
        GeneralUtility::rmdir($tempPathRenamed, true);

        /** @var \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface $cacheFrontend */
        $cacheFrontend = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Cache\\CacheManager')->getCache('l10n');
        $cacheFrontend->flush();
    }
}
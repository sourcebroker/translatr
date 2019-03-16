<?php

namespace SourceBroker\Translatr\Hooks;

use SourceBroker\Translatr\Database\Database;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class TceMain
 *
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
            if ($status === 'new') {
                $id = $pObj->substNEWwithIDs[$id];
            }

            $record = BackendUtility::getRecord($table, $id);
            /** @var Database $db */
            $db = GeneralUtility::makeInstance($GLOBALS['TYPO3_CONF_VARS']['EXT']['EXTCONF']['translatr']['database']);

            if (empty($record['ukey'])) {
                /** @var \TYPO3\CMS\Core\Messaging\FlashMessage $message */
                $message = GeneralUtility::makeInstance(
                    \TYPO3\CMS\Core\Messaging\FlashMessage::class,
                    'Ukey field value can\'t be empty',
                    'Translatr',
                    \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR,
                    true
                );

                /** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
                $flashMessageService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessageService::class);
                $flashMessageService->getMessageQueueByIdentifier()->addMessage($message);

                $db->update('tx_translatr_domain_model_label', ['deleted' => 1], ['uid' => (int)$id]);
            } else {
                preg_match('/^EXT\:([a-z\_]+)\//', $record['ll_file'], $matches);

                if (isset($matches[1])) {
                    if ($record['extension'] != $matches[1]) {
                        $db->update('tx_translatr_domain_model_label', ['extension' => $matches[1]], ['uid' => (int)$id]);
                    }
                }
            }
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

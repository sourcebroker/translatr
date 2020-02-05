<?php

namespace SourceBroker\Translatr\Hooks;

use SourceBroker\Translatr\Database\Database;
use SourceBroker\Translatr\Service\CacheCleaner;
use SourceBroker\Translatr\Utility\FileUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
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
     * @param DataHandler $pObj
     */
    public function processCmdmap_postProcess(
        $command,
        $table,
        $id,
        $value,
        DataHandler &$pObj
    ) {
        if ($table == 'tx_translatr_domain_model_label' && $command == 'delete') {
            GeneralUtility::makeInstance(CacheCleaner::class)->flushCache();
            FileUtility::getTempFolderPath();
        }
    }

    /**
     * @param $status
     * @param $table
     * @param $id
     * @param array $fieldArray
     * @param DataHandler $pObj
     */
    public function processDatamap_afterDatabaseOperations(
        $status,
        $table,
        $id,
        array $fieldArray,
        DataHandler &$pObj
    ) {
        if ($table == 'tx_translatr_domain_model_label') {
            if ($status === 'new') {
                $id = $pObj->substNEWwithIDs[$id];
            }
            $record = BackendUtility::getRecord($table, $id);
            /** @var Database $db */
            $db = GeneralUtility::makeInstance($GLOBALS['TYPO3_CONF_VARS']['EXT']['EXTCONF']['translatr']['database']);

            if (empty($record['ukey'])) {
                /** @var FlashMessage $message */
                $message = GeneralUtility::makeInstance(
                    FlashMessage::class,
                    'Ukey field value can\'t be empty',
                    'Translatr',
                    FlashMessage::ERROR,
                    true
                );

                /** @var $flashMessageService FlashMessageService */
                $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
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
            $db->update('tx_translatr_domain_model_label', ['modify' => 1], ['uid' => (int)$id]);
        }
    }
}

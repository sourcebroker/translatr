<?php

namespace SourceBroker\Translatr\Hooks;

use SourceBroker\Translatr\Database\Database;
use SourceBroker\Translatr\Database\DatabaseInterface;
use SourceBroker\Translatr\Service\CacheCleaner;
use SourceBroker\Translatr\Utility\FileUtility;
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
    public function processCmdmap_postProcess(
        $command,
        $table,
        $id,
        $value,
        DataHandler $pObj
    ) {
        if ($table === 'tx_translatr_domain_model_label' && $command === 'delete') {
            GeneralUtility::makeInstance(CacheCleaner::class)->flushCache();
            FileUtility::getTempFolderPath();
        }
    }

    public function processDatamap_afterDatabaseOperations(
        $status,
        $table,
        $id,
        array $fieldArray,
        DataHandler $pObj
    ) {
        if ($table === 'tx_translatr_domain_model_label') {
            /** @var DatabaseInterface $db */
            $db = GeneralUtility::makeInstance(Database::class);
            if ($status === 'new') {
                $id = $pObj->substNEWwithIDs[$id];
                if (empty($fieldArray['ukey'])) {
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
                    $db->delete('tx_translatr_domain_model_label', ['uid' => (int)$id]);
                }
            }
            $db->update('tx_translatr_domain_model_label', ['modify' => 1], ['uid' => (int)$id]);
        }
    }
}

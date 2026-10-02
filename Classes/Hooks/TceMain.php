<?php

namespace SourceBroker\Translatr\Hooks;

use SourceBroker\Translatr\Database\Database;
use SourceBroker\Translatr\Database\DatabaseInterface;
use SourceBroker\Translatr\Service\CacheCleaner;
use SourceBroker\Translatr\Utility\FileUtility;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TceMain
{
    /**
     * @param string $command
     * @param string $table
     * @param int|string $id
     * @param mixed $value
     */
    public function processCmdmap_postProcess(
        $command,
        $table,
        $id,
        $value,
        DataHandler $pObj
    ): void {
        if ($table === 'tx_translatr_domain_model_label' && $command === 'delete') {
            GeneralUtility::makeInstance(CacheCleaner::class)->flushCache();
            FileUtility::getTempFolderPath();
        }
    }

    /**
     * @param string $status
     * @param string $table
     * @param int|string $id
     * @param array<string, mixed> $fieldArray
     */
    public function processDatamap_afterDatabaseOperations(
        $status,
        $table,
        $id,
        array $fieldArray,
        DataHandler $pObj
    ): void {
        if ($table === 'tx_translatr_domain_model_label') {
            /** @var DatabaseInterface $db */
            $db = GeneralUtility::makeInstance(Database::class);
            if ($status === 'new') {
                $id = $pObj->substNEWwithIDs[$id] ?? null;
                if (empty($fieldArray['ukey'])) {
                    /** @var FlashMessage $message */
                    $message = GeneralUtility::makeInstance(
                        FlashMessage::class,
                        'Ukey field value can\'t be empty',
                        'Translatr',
                        ContextualFeedbackSeverity::ERROR,
                        // CLI users have no session to store the message in
                        !Environment::isCli()
                    );
                    /** @var FlashMessageService $flashMessageService */
                    $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
                    $flashMessageService->getMessageQueueByIdentifier()->addMessage($message);
                    $db->delete('tx_translatr_domain_model_label', ['uid' => (int)$id]);

                    return;
                }
            }
            $db->update('tx_translatr_domain_model_label', ['modify' => 1], ['uid' => (int)$id]);
        }
    }
}

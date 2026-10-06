<?php

declare(strict_types=1);

namespace SourceBroker\Translatr\Hooks;

use SourceBroker\Translatr\Database\LabelWriter;
use SourceBroker\Translatr\Service\LabelIndexer;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

class TceMain
{
    /** @var array<int, array{extension: string, file: string}> */
    private array $deletedSources = [];

    public function __construct(
        private readonly LabelWriter $labelWriter,
        private readonly LabelIndexer $labelIndexer,
        private readonly FlashMessageService $flashMessageService,
    ) {}

    /** @param array<string, mixed> $record */
    public function processCmdmap_deleteAction(
        string $table,
        int $uid,
        array $record,
        bool &$recordWasDeleted,
        DataHandler $dataHandler
    ): void {
        if ($table === 'tx_translatr_domain_model_label' && ($record['language'] ?? '') === 'default') {
            $this->deletedSources[$uid] = ['extension' => (string)$record['extension'], 'file' => (string)$record['ll_file']];
        }
    }

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
        if ($table === 'tx_translatr_domain_model_label' && $command === 'delete' && isset($this->deletedSources[(int)$id])) {
            $source = $this->deletedSources[(int)$id];
            unset($this->deletedSources[(int)$id]);
            $this->labelIndexer->invalidateSourceFile($source['extension'], $source['file']);
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
            if ($status === 'new') {
                $id = $pObj->substNEWwithIDs[$id] ?? null;
                if (empty($fieldArray['ukey'])) {
                    $message = new FlashMessage(
                        'Ukey field value can\'t be empty',
                        'Translatr',
                        ContextualFeedbackSeverity::ERROR,
                        // CLI users have no session to store the message in
                        !Environment::isCli()
                    );
                    $this->flashMessageService->getMessageQueueByIdentifier()->addMessage($message);
                    $this->labelWriter->delete((int)$id);

                    return;
                }
            }
            $this->labelWriter->markModified((int)$id);
        }
    }
}

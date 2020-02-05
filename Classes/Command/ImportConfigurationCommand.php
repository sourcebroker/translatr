<?php
declare(strict_types=1);

namespace SourceBroker\Translatr\Command;

use SourceBroker\Translatr\Service\ImportProcess;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class ImportConfigurationCommand
 * @package SourceBroker\Translatr\Command
 */
class ImportConfigurationCommand extends Command
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var ImportProcess
     */
    protected $importProcessService;

    /**
     * Configure the command by defining the name, options and arguments
     */
    protected function configure(): void
    {
        $this->setAliases(['lang:import:config']);
        $this->setDescription('Import configuration from YAML into Translatr');
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->importProcessService = $this->objectManager->get(ImportProcess::class);
    }

    /**
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Import of Translatr Configuration started');
        $dataToImport = $this->importProcessService->getDataToImport();
        $progressBar = new ProgressBar(
            !$output->isVerbose() ? new NullOutput() : $output,
            count($dataToImport)
        );
        foreach ($dataToImport as $configuration) {
            $output->writeln('Extension processing: ' . $configuration['extension']);
            foreach ($configuration['files'] as $file) {
                $output->writeln('File processing: ' . $file['path']);
                $this->importProcessService->importDataFromSingleFile(
                    $configuration['extension'],
                    $file
                );
            }
            $progressBar->advance();
        }
        $output->writeln('Import finished');
        $progressBar->finish();

        return 0;
    }
}

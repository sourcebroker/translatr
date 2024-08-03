<?php
declare(strict_types=1);

namespace SourceBroker\Translatr\Command;

use SourceBroker\Translatr\Service\CacheCleaner;
use SourceBroker\Translatr\Service\ImportProcess;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;

class ImportConfigurationCommand extends Command
{
    protected ImportProcess $importProcessService;

    protected CacheCleaner $cacheCleaner;

    protected function configure(): void
    {
        $this->setAliases(['translatr:import:config']);
        $this->addOption(
            'fail-on-connection-error',
            null,
            InputOption::VALUE_NONE,
            'Instead of exiting this command if database connection problems, throw an error.'
        );
        $this->setDescription('Import configuration for labels for ext:translatr');
        $this->importProcessService = GeneralUtility::makeInstance(ImportProcess::class);
        $this->cacheCleaner = GeneralUtility::makeInstance(CacheCleaner::class);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->isDatabaseConnection($input, $output)) {
            $output->writeln('<warning>Skipping label import as no database connection.</warning>');
            return Command::SUCCESS;
        }

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
        $this->cacheCleaner->flushCache();
        $progressBar->finish();

        return Command::SUCCESS;
    }

    public function isDatabaseConnection(InputInterface $input, OutputInterface $output): bool
    {
        try {
            if (!GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME)
                ->isConnected()) {
                if ($input->getOption('fail-on-connection-error')) {
                    throw new \RuntimeException('No connection to database');
                }
                return false;
            }
            return true;
        } catch (\Exception $e) {
            if ($input->getOption('fail-on-connection-error')) {
                throw new \RuntimeException($e->getMessage());
            }
            return false;
        }
    }
}

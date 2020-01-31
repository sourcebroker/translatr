<?php
declare(strict_types=1);

namespace SourceBroker\Translatr\Command;

use SourceBroker\Translatr\Service\Tags\ImportProcess;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class ImportTagsCommand
 * @package SourceBroker\Translatr\Command
 */
class ImportTagsCommand extends Command
{
    /**
     * @var ObjectManager
     */
    protected $objectManger;

    /**
     * @var ImportProcess
     */
    protected $importProcessService;

    /**
     * Configure the command by defining the name, options and arguments
     */
    protected function configure(): void
    {
        $this->setAliases(['lang:fe:tags']);
        $this->setDescription('Import labels tags from config file into Translatr');
        $this->objectManger = GeneralUtility::makeInstance(ObjectManager::class);
        $this->importProcessService = $this->objectManger->get(ImportProcess::class);
    }

    /**
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
//        $output->writeln('Works');
        $this->importProcessService->import($output);

        return 0;
    }
}

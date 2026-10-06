<?php

declare(strict_types=1);

namespace SourceBroker\Translatr\Command;

use SourceBroker\Translatr\Service\ExtensionProvider;
use SourceBroker\Translatr\Service\LabelIndexer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class IndexLabelsCommand extends Command
{
    public function __construct(
        private readonly ExtensionProvider $extensionProvider,
        private readonly LabelIndexer $labelIndexer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Index changed source labels of configured extensions')
            ->addArgument('extension', InputArgument::OPTIONAL, 'Configured extension key; omit to index all configured extensions')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Re-index all source files even if their hashes have not changed');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $extensions = $this->extensionProvider->getItems();
        $extension = $input->getArgument('extension');
        if ($extension !== null) {
            if (!isset($extensions[$extension])) {
                $io->error('The extension must be loaded and configured in tx_translatr.extensions.');
                return Command::INVALID;
            }
            $extensions = [$extension => $extension];
        }
        if ($extensions === []) {
            $io->note('No extensions configured for Translatr.');
            return Command::SUCCESS;
        }

        $count = 0;
        foreach ($extensions as $extensionKey) {
            $indexedFiles = $this->labelIndexer->index($extensionKey, (bool)$input->getOption('force'));
            $count += $indexedFiles;
            $io->writeln(sprintf('%s: %d source file(s) indexed.', $extensionKey, $indexedFiles));
        }
        $io->success(sprintf('Indexing complete: %d source file(s) updated.', $count));
        return Command::SUCCESS;
    }
}

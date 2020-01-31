<?php
declare(strict_types=1);

namespace SourceBroker\Translatr\Service\Tags;

use SourceBroker\Translatr\Domain\Repository\LabelRepository;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class TagsService
 * @package SourceBroker\Translatr\Service
 */
class ImportProcess
{
    const INCLUDED_IN_IMPORT = ['tags'];

    /**
     * @var ObjectManager
     */
    protected $objectManger;

    /**
     * @var YamlFileHandler
     */
    protected $yamlFileHandler;

    /**
     * @var LabelRepository
     */
    protected $labelRepository;

    /**
     * TagsService constructor.
     */
    public function __construct()
    {
        $this->objectManger = GeneralUtility::makeInstance(ObjectManager::class);
        $this->yamlFileHandler = $this->objectManger->get(YamlFileHandler::class);
        $this->labelRepository = $this->objectManger->get(LabelRepository::class);
    }

    public function import(OutputInterface $output): void
    {
        foreach ($this->yamlFileHandler->getConfiguration() as $configuration) {
            $output->writeln('Extension processing: ' . $configuration['extension']);
            $this->importDataFromSingleFile($configuration['extension'], $configuration['files'], $output);
        }
    }

    public function importDataFromSingleFile(string $extension, array $files, OutputInterface $output): void
    {
        $config = [];
        foreach ($files as $file) {
            $output->writeln('File processing: ' . $file['path']);
            foreach ($file['labels'] as $key => $properties) {
                foreach ($properties as $propertyName => $property) {
                    $config[] = [
                        'label' => $key,
                        'tags' => implode(',', $property)
                    ];
                }
            }
        }
        $output->writeln(print_r($config, true));
    }

}

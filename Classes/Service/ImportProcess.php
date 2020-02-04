<?php
declare(strict_types=1);

namespace SourceBroker\Translatr\Service;

use SourceBroker\Translatr\Domain\Repository\LabelRepository;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class ImportProcess
 * @package SourceBroker\Translatr\Service
 */
class ImportProcess
{
    /**
     * Should contain comma separated values
     */
    const ALLOWED_PROPERTIES = ['tags'];

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
     * ImportProcess constructor.
     */
    public function __construct()
    {
        $this->objectManger = GeneralUtility::makeInstance(ObjectManager::class);
        $this->yamlFileHandler = $this->objectManger->get(YamlFileHandler::class);
        $this->labelRepository = $this->objectManger->get(LabelRepository::class);
    }

    /**
     * @return array
     */
    public function getDataToImport(): array
    {
        return $this->yamlFileHandler->getConfiguration();
    }

    /**
     * @param string $extension
     * @param array $file
     */
    public function importDataFromSingleFile(string $extension, array $file): void
    {
        foreach ($file['labels'] as $key => $properties) {
            $values = [];
            foreach ($properties as $propertyName => $property) {
                if (in_array($propertyName, self::ALLOWED_PROPERTIES)) {
                    $values[$propertyName] = implode(',', array_map('trim', $property));
                }
            }
            if (count($values)) {
                $this->labelRepository->updateSelectedRow(
                    $key,
                    $extension,
                    $file['path'],
                    $values
                );
            }
        }
    }
}

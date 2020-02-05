<?php
declare(strict_types=1);

namespace SourceBroker\Translatr\Service;

use SourceBroker\Translatr\Domain\Model\Dto\BeLabelDemand;
use SourceBroker\Translatr\Domain\Repository\LabelRepository;
use SourceBroker\Translatr\Utility\LanguageUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

/**
 * Class ImportProcess
 * @package SourceBroker\Translatr\Service
 */
class ImportProcess extends BaseService
{
    /**
     * Should contain comma separated values
     */
    const ALLOWED_PROPERTIES = ['tags'];

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
        parent::__construct();
        $this->yamlFileHandler = $this->objectManager->get(YamlFileHandler::class);
        $this->labelRepository = $this->objectManager->get(LabelRepository::class);
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
        $this->pushMissingKeyToDatabase($extension, $file['labels'], $file['path']);
        foreach ($file['labels'] as $key => $properties) {
            $values = [];
            foreach ($properties as $propertyName => $property) {
                if (in_array($propertyName, self::ALLOWED_PROPERTIES)) {
                    $values[$propertyName] = implode(',', array_map('trim', $property));
                }
            }
            if (count($values)) {
                $this->labelRepository->updateSelectedRowInAllLanguages(
                    $key,
                    $extension,
                    $file['path'],
                    $values
                );
            }
        }
    }

    /**
     * @param string $extension
     * @param array $keys
     * @param string $path
     */
    protected function pushMissingKeyToDatabase(string $extension, array $keys, string $path): void
    {
        $allLanguages = array_keys(LanguageUtility::getAvailableLanguages());
        $demand = $this->objectManager->get(BeLabelDemand::class);
        $demand->setExtension($extension);
        $demand->setKeys(array_keys($keys));
        $demand->setLanguages($allLanguages);
        foreach ($this->labelRepository->findDemandedForBe($demand) as $label) {
            foreach ($allLanguages as $language) {
                $parsedLabels = LanguageUtility::parseLanguageLabels($path, $language);
                if (isset($parsedLabels[$language], $parsedLabels[$language][$label['ukey']]) && !empty($parsedLabels[$language][$label['ukey']][0]['target'])) {
                    if (isset($label['language_childs'][$language])) {
                        if (empty($label['language_childs'][$language]['modify'])) {
                            $this->labelRepository->updateSelectedRow(
                                $label['language_childs'][$language]['uid'],
                                [
                                    'text' => $parsedLabels[$language][$label['ukey']][0]['target']
                                ]
                            );
                        }
                    } else {
                        $this->labelRepository->createLanguageChildFromDefault(
                            $label,
                            $parsedLabels[$language][$label['ukey']][0]['target'],
                            $language
                        );
                    }
                }
            }
        }
        $this->objectManager->get(PersistenceManager::class)->persistAll();
    }
}

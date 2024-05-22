<?php
declare(strict_types=1);

namespace SourceBroker\Translatr\Service;

use SourceBroker\Translatr\Domain\Model\Dto\BeLabelDemand;
use SourceBroker\Translatr\Domain\Repository\LabelRepository;
use SourceBroker\Translatr\Utility\LanguageUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

class ImportProcess extends BaseService
{
    /**
     * Should contain comma separated values
     */
    const ALLOWED_PROPERTIES = ['tags'];

    protected YamlFileHandler $yamlFileHandler;

    protected LabelRepository $labelRepository;

    public function __construct()
    {
        parent::__construct();
        $this->yamlFileHandler = GeneralUtility::makeInstance(YamlFileHandler::class);
        $this->labelRepository = GeneralUtility::makeInstance(LabelRepository::class);
    }

    public function getDataToImport(): array
    {
        return $this->yamlFileHandler->getConfiguration();
    }

    public function importDataFromSingleFile(string $extension, array $file): void
    {
        $this->labelRepository->indexExtensionLabels($extension);
        $this->pushMissingKeyTranslationsToDatabase($extension, $file['labels'], $file['path']);
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

    protected function pushMissingKeyTranslationsToDatabase(string $extension, array $keys, string $path): void
    {
        $allLanguages = array_keys(LanguageUtility::getAvailableLanguages());
        $demand = GeneralUtility::makeInstance(BeLabelDemand::class);
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
        GeneralUtility::makeInstance(PersistenceManager::class)->persistAll();
    }
}

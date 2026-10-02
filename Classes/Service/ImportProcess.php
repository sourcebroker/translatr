<?php

declare(strict_types=1);

namespace SourceBroker\Translatr\Service;

use SourceBroker\Translatr\Domain\Model\Dto\BeLabelDemand;
use SourceBroker\Translatr\Domain\Repository\LabelRepository;
use SourceBroker\Translatr\Utility\LanguageUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

class ImportProcess
{
    public const ALLOWED_PROPERTIES = ['tags'];

    protected YamlFileHandler $yamlFileHandler;

    protected LabelRepository $labelRepository;

    public function __construct()
    {
        $this->yamlFileHandler = GeneralUtility::makeInstance(YamlFileHandler::class);
        $this->labelRepository = GeneralUtility::makeInstance(LabelRepository::class);
    }

    /**
     * @return list<array{extension: string, files?: list<array{fileName: string, path: string, labels: array<string, mixed>}>}>
     */
    public function getDataToImport(): array
    {
        return $this->yamlFileHandler->getConfiguration();
    }

    /**
     * @param array{fileName: string, path: string, labels: array<string, mixed>} $file
     */
    public function importDataFromSingleFile(string $extension, array $file): void
    {
        $this->labelRepository->indexExtensionLabels($extension);
        $this->pushMissingKeyTranslationsToDatabase($extension, $file['labels'], $file['path']);
        foreach ($file['labels'] as $key => $properties) {
            $values = [];
            foreach ($properties as $propertyName => $property) {
                if (in_array($propertyName, self::ALLOWED_PROPERTIES)) {
                    $values[$propertyName] = implode(',', array_map(trim(...), $property));
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
     * @param array<string, mixed> $keys
     */
    protected function pushMissingKeyTranslationsToDatabase(string $extension, array $keys, string $path): void
    {
        $availableLanguages = LanguageUtility::getAvailableLanguages();
        if (is_array($availableLanguages)) {
            $allLanguages = array_keys($availableLanguages);
            $demand = GeneralUtility::makeInstance(BeLabelDemand::class);
            $demand->setExtension($extension);
            $demand->setKeys(array_keys($keys));
            $demand->setLanguages($allLanguages);
            $labels = $this->labelRepository->findDemandedForBe($demand);
            // Parse the file once per language, not for every label
            $parsedLabelsByLanguage = [];
            if ($labels !== []) {
                foreach ($allLanguages as $language) {
                    $parsedLabelsByLanguage[$language] = LanguageUtility::parseLanguageLabels($path, $language)[$language] ?? [];
                }
            }
            foreach ($labels as $label) {
                foreach ($allLanguages as $language) {
                    $translation = $parsedLabelsByLanguage[$language][$label['ukey']][0]['target'] ?? null;
                    if (!empty($translation)) {
                        if (isset($label['language_childs'][$language])) {
                            if (empty($label['language_childs'][$language]['modify'])) {
                                $this->labelRepository->updateSelectedRow(
                                    $label['language_childs'][$language]['uid'],
                                    [
                                        'text' => $translation,
                                    ]
                                );
                            }
                        } else {
                            $this->labelRepository->createLanguageChildFromDefault(
                                $label,
                                $translation,
                                $language
                            );
                        }
                    }
                }
            }
            GeneralUtility::makeInstance(PersistenceManager::class)->persistAll();
        }
    }
}

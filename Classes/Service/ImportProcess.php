<?php

declare(strict_types=1);

namespace SourceBroker\Translatr\Service;

use SourceBroker\Translatr\Database\LabelReader;
use SourceBroker\Translatr\Database\LabelWriter;
use SourceBroker\Translatr\Domain\Model\Dto\BeLabelDemand;
use SourceBroker\Translatr\Domain\Repository\LabelRepository;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

class ImportProcess
{
    public function __construct(
        private readonly YamlFileHandler $yamlFileHandler,
        private readonly LabelRepository $labelRepository,
        private readonly LabelReader $labelReader,
        private readonly LabelWriter $labelWriter,
        private readonly LabelIndexer $labelIndexer,
        private readonly LanguageService $languageService,
        private readonly PersistenceManagerInterface $persistenceManager,
    ) {}

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
        $this->labelIndexer->index($extension);
        $this->pushMissingKeyTranslationsToDatabase($extension, $file['labels'], $file['path']);
        foreach ($file['labels'] as $key => $properties) {
            if (isset($properties['tags'])) {
                $this->labelWriter->updateTags(
                    $key,
                    $extension,
                    $file['path'],
                    implode(',', array_map(trim(...), $properties['tags']))
                );
            }
        }
    }

    /**
     * @param array<string, mixed> $keys
     */
    protected function pushMissingKeyTranslationsToDatabase(string $extension, array $keys, string $path): void
    {
        if ($keys === []) {
            return;
        }
        $availableLanguages = $this->languageService->getAvailableLanguages();
        if (is_array($availableLanguages)) {
            $allLanguages = array_keys($availableLanguages);
            $demand = new BeLabelDemand();
            $demand->setExtension($extension);
            $demand->setKeys(array_keys($keys));
            $demand->setLanguages($allLanguages);
            $labels = $this->labelReader->findDemandedForBe($demand, $path);
            // Parse the file once per language, not for every label
            $parsedLabelsByLanguage = [];
            if ($labels !== []) {
                foreach ($allLanguages as $language) {
                    $parsedLabelsByLanguage[$language] = $this->languageService->parseLanguageLabels($path, $language)[$language] ?? [];
                }
            }
            foreach ($labels as $label) {
                foreach ($allLanguages as $language) {
                    $translation = $parsedLabelsByLanguage[$language][$label['ukey']][0]['target'] ?? null;
                    if (!empty($translation)) {
                        if (isset($label['language_childs'][$language])) {
                            if (empty($label['language_childs'][$language]['modify'])) {
                                $this->labelWriter->updateTranslation(
                                    (int)$label['language_childs'][$language]['uid'],
                                    $translation
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
            $this->persistenceManager->persistAll();
        }
    }
}

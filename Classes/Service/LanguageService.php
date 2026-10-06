<?php

declare(strict_types=1);

namespace SourceBroker\Translatr\Service;

use SourceBroker\Translatr\Configuration\Configurator;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\LocalizationFactory;

final readonly class LanguageService
{
    public function __construct(
        private Configurator $configurator,
        private LocalizationFactory $localizationFactory,
        private Typo3Version $typo3Version,
        private SourceLabelParser $sourceLabelParser,
    ) {}

    /**
     * @return array<string, string>|null
     */
    public function getAvailableLanguages(): ?array
    {
        $languages = $this->configurator->getOption('languages');

        return is_array($languages) ? $languages : null;
    }

    /**
     * Read the source file directly: indexing must not reuse stale localization caches or generated overrides.
     *
     * @return array<string, string>
     */
    public function readSourceLabels(string $file): array
    {
        $contents = @file_get_contents($file);
        if ($contents === false) {
            throw new \RuntimeException('Could not read language file: ' . $file, 1791214301);
        }
        return $this->parseSourceLabels($contents, $file);
    }

    /** @return array<string, string> */
    public function parseSourceLabels(string $contents, string $source): array
    {
        return $this->sourceLabelParser->parse($contents, $source);
    }

    /**
     * Returns labels in the TYPO3 13 structure for all supported TYPO3 versions:
     * [$language => [$key => [['source' => '...', 'target' => '...']]]]
     *
     * @return array<string, mixed>
     */
    public function parseLanguageLabels(string $file, string $language): array
    {
        if ($this->typo3Version->getMajorVersion() < 14) {
            return $this->localizationFactory->getParsedData($file, $language);
        }

        // TYPO3 14 (#107436) returns a flat [$key => $text] array, with values of the fallback
        // languages ("en", "default") merged in for keys which are not translated.
        $labels = $this->localizationFactory->getParsedData($file, $language);
        $defaultLabels = $language === 'default'
            ? $labels
            : $this->localizationFactory->getParsedData($file, 'default');
        $parsedLabels = [];
        foreach ($labels as $key => $text) {
            if ($language !== 'default' && $text === ($defaultLabels[$key] ?? null)) {
                // Fallback value, not a translation
                continue;
            }
            $parsedLabels[$key] = [['source' => $defaultLabels[$key] ?? $text, 'target' => $text]];
        }

        return [$language => $parsedLabels];
    }
}

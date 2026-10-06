<?php

declare(strict_types=1);

namespace SourceBroker\Translatr\ViewHelpers\Be;

use SourceBroker\Translatr\Service\LanguageService;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class TranslateViewHelper extends AbstractViewHelper
{
    public function __construct(
        private readonly LanguageService $languageService,
    ) {}

    public function initializeArguments(): void
    {
        $this->registerArgument('llFile', 'string', 'Path to the locallang file', true);
        $this->registerArgument('language', 'string', 'Translation target language', true);
        $this->registerArgument('key', 'string', 'Label key', true);
    }

    /**
     * We don't need the cache of parse files, because it's done on the parser factory already
     */
    public function render(): string
    {
        /** @var string $language */
        $language = $this->arguments['language'];
        /** @var string $llFile */
        $llFile = $this->arguments['llFile'];
        /** @var string $key */
        $key = $this->arguments['key'];

        $parsedLabels = $this->languageService->parseLanguageLabels($llFile, $language);

        return $parsedLabels[$language][$key][0]['target'] ?? '';
    }
}

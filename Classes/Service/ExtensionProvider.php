<?php

declare(strict_types=1);

namespace SourceBroker\Translatr\Service;

use SourceBroker\Translatr\Configuration\Configurator;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

final readonly class ExtensionProvider
{
    public function __construct(
        private Configurator $configurator,
    ) {}

    /**
     * @return array<string, string>
     */
    public function getItems(): array
    {
        $extensions = array_intersect(
            (array)$this->configurator->getOption('extensions'),
            ExtensionManagementUtility::getLoadedExtensionListArray()
        );
        sort($extensions);

        return array_combine($extensions, $extensions);
    }
}

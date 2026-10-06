<?php

declare(strict_types=1);

namespace SourceBroker\Translatr\Backend;

use SourceBroker\Translatr\Domain\Model\Dto\BeLabelDemand;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

class LabelFilterState
{
    private const EXTENSION_KEY = 'translatr/recentlySelectedModule';
    private const LANGUAGES_KEY = 'translatr/recentlySelectedLanguages';

    /**
     * @param array<string, string> $extensions
     * @param array<string, string> $languages
     */
    public function resolve(BeLabelDemand $demand, array $extensions, array $languages): BeLabelDemand
    {
        $user = $this->getBackendUser();
        $extension = $demand->getExtension() ?: $user->getModuleData(self::EXTENSION_KEY);
        $selectedLanguages = $demand->getLanguages() ?? $user->getModuleData(self::LANGUAGES_KEY);
        $demand->setExtension(is_string($extension) && isset($extensions[$extension]) ? $extension : '');
        $demand->setLanguages(is_array($selectedLanguages)
            ? array_values(array_unique(array_intersect($selectedLanguages, array_keys($languages))))
            : []);
        $this->remember($demand->getExtension() ?? '', $demand->getLanguages() ?? []);
        return $demand;
    }

    /**
     * @param array<int|string, string> $languages
     */
    public function remember(string $extension, array $languages): void
    {
        $user = $this->getBackendUser();
        $user->pushModuleData(self::EXTENSION_KEY, $extension);
        $user->pushModuleData(self::LANGUAGES_KEY, $languages);
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}

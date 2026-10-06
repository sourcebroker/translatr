<?php

declare(strict_types=1);

namespace SourceBroker\Translatr\Service;

use TYPO3\CMS\Core\Information\Typo3Version;

final readonly class OverrideLoaderContentBuilder
{
    /**
     * @param array<string, list<array{overwritten: string, overwriteWith: string}>> $translationOverrideFiles
     */
    public function build(array $translationOverrideFiles): string
    {
        $code = '<?php' . PHP_EOL;
        $isVersion14 = (new Typo3Version())->getMajorVersion() >= 14;
        foreach ($translationOverrideFiles as $languageCode => $fileDatasets) {
            foreach ($fileDatasets as $fileData) {
                $overwrittenPaths = [$fileData['overwritten']];
                if (str_starts_with($fileData['overwritten'], 'EXT:')) {
                    $overwrittenPaths[] = str_replace('EXT:', 'typo3conf/ext/', $fileData['overwritten']);
                }
                foreach ($overwrittenPaths as $overwrittenPath) {
                    $code .= $this->buildOverrideRow(
                        $languageCode,
                        $overwrittenPath,
                        $fileData['overwriteWith'],
                        $isVersion14,
                    );
                }
            }
        }

        return $code;
    }

    private function buildOverrideRow(
        string $languageCode,
        string $overwritten,
        string $overwriteWith,
        bool $isVersion14,
    ): string {
        $overridesPath = '[\'SYS\'][\'locallangXMLOverride\']';
        if ($isVersion14) {
            $overridesPath = '[\'LANG\'][\'resourceOverrides\']';
            $languageCode = str_replace('_', '-', $languageCode);
        }

        return '$GLOBALS[\'TYPO3_CONF_VARS\']' . $overridesPath
            . '[' . var_export($languageCode, true) . ']'
            . '[' . var_export($overwritten, true) . '][] = '
            . var_export($overwriteWith, true) . ';' . PHP_EOL;
    }
}

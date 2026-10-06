<?php

declare(strict_types=1);

namespace SourceBroker\Translatr\Service;

use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Package\PackageManager;

class YamlFileHandler
{
    public const LANG_FILE_PATH = '/Resources/Private/Language/';
    public const ROOT_NAME = 'ext';
    public const FILENAME = 'Configuration.yaml';

    public function __construct(
        private readonly YamlFileLoader $yamlFileLoader,
        private readonly PackageManager $packageManager,
    ) {}

    /**
     * @return list<array{extension: string, files?: list<array{fileName: string, path: string, labels: array<string, mixed>}>}>
     */
    public function getConfiguration(): array
    {
        $configuration = [];
        $i = 0;
        foreach ($this->getGlobalConfiguration() as $extensionName => $files) {
            $configuration[$i]['extension'] = $extensionName;
            foreach ($files as $fileName => $labels) {
                $configuration[$i]['files'][] = [
                    'fileName' => $fileName,
                    'path' => 'EXT:' . $extensionName . self::LANG_FILE_PATH . $fileName,
                    'labels' => $labels,
                ];
            }
            $i++;
        }

        return $configuration;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function getGlobalConfiguration(): array
    {
        $configuration = [];
        foreach ($this->getYamlFilesFromPackages() as $file) {
            $fileContent = $this->readSingleFile($file);
            if (isset($fileContent[self::ROOT_NAME])) {
                foreach ($fileContent[self::ROOT_NAME] as $ext => $files) {
                    if (!array_key_exists($ext, $configuration)) {
                        $configuration[$ext] = [];
                    }
                    $this->populateRows($configuration[$ext], $files);
                }
            }
        }

        return $configuration;
    }

    /**
     * @param array<string, mixed> $configuration
     * @param array<string, mixed> $files
     */
    protected function populateRows(array &$configuration, array $files): void
    {
        foreach ($files as $langFile => $rows) {
            if (!array_key_exists($langFile, $configuration)) {
                $configuration[$langFile] = [];
            }
            if (!is_array($rows)) {
                continue;
            }
            foreach ($rows as $key => $properties) {
                if (!array_key_exists($key, $configuration[$langFile])) {
                    $configuration[$langFile][$key] = [];
                }
                foreach ($properties as $property => $values) {
                    if (!array_key_exists($property, $configuration[$langFile][$key])) {
                        $configuration[$langFile][$key][$property] = $values;
                    } else {
                        $configuration[$langFile][$key][$property] = array_unique(
                            array_merge($configuration[$langFile][$key][$property], $values)
                        );
                    }
                }
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function readSingleFile(string $file): array
    {
        return $this->yamlFileLoader->load($file, 0);
    }

    /**
     * @return list<string>
     */
    protected function getYamlFilesFromPackages(): array
    {
        $files = [];
        foreach ($this->packageManager->getActivePackages() as $package) {
            $yamlFile = $package->getPackagePath() . 'Configuration/Translation/' . self::FILENAME;
            if (is_file($yamlFile)) {
                $files[] = $yamlFile;
            }
        }

        return $files;
    }
}

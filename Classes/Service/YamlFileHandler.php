<?php
declare(strict_types=1);

namespace SourceBroker\Translatr\Service;

use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class YamlFileHandler extends BaseService
{
    public const LANG_FILE_PATH = '/Resources/Private/Language/';
    public const ROOT_NAME = 'ext';
    public const FILENAME = 'Configuration.yaml';

    protected YamlFileLoader $yamlFileLoader;

    protected PackageManager $packageManager;

    public function __construct()
    {
        parent::__construct();
        $this->packageManager = GeneralUtility::makeInstance(PackageManager::class);
        $this->yamlFileLoader = GeneralUtility::makeInstance(YamlFileLoader::class);
    }

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
                    'labels' => $labels
                ];
            }
            $i++;
        }

        return $configuration;
    }

    protected function getGlobalConfiguration(): array
    {
        $configuration = [];
        foreach ($this->getYamlFilesFromPackages() as $file) {
            $fileContent = $this->readSingleFile($file);
            if (isset($fileContent[self::ROOT_NAME])) {
                foreach ($fileContent[self::ROOT_NAME] as $ext => $files) {
                    if (!key_exists($ext, $configuration)) {
                        $configuration[$ext] = [];
                    }
                    $this->populateRows($configuration[$ext], $files);
                }
            }
        }

        return $configuration;
    }

    protected function populateRows(array &$configuration, array $files): void
    {
        foreach ($files as $langFile => $rows) {
            if (!key_exists($langFile, $configuration)) {
                $configuration[$langFile] = [];
            }
            if (!is_array($rows)) {
                continue;
            }
            foreach ($rows as $key => $properties) {
                if (!key_exists($key, $configuration[$langFile])) {
                    $configuration[$langFile][$key] = [];
                }
                foreach ($properties as $property => $values) {
                    if (!key_exists($property, $configuration[$langFile][$key])) {
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

    protected function readSingleFile(string $file): array
    {
        return $this->yamlFileLoader->load($file, 0);
    }

    protected function getYamlFilesFromPackages(): array
    {
        $files = [];
        foreach ($this->packageManager->getActivePackages() as $package) {
            $yamlFile = $package->getPackagePath() . 'Configuration/Translation/' . self::FILENAME;
            if (@is_file($yamlFile)) {
                $files[] = $yamlFile;
            }
        }

        return $files;
    }
}

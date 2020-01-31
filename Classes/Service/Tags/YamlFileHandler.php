<?php
declare(strict_types=1);

namespace SourceBroker\Translatr\Service\Tags;

use Symfony\Component\Finder\Finder;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Console\CommandNameAlreadyInUseException;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class TagsService
 * @package SourceBroker\Translatr\Service
 */
class YamlFileHandler
{
    const LANG_PATH = '/Resources/Private/Language/';
    const RESERVED_NAME = 'presets';

    /**
     * @var ObjectManager
     */
    protected $objectManger;

    /**
     * @var YamlFileLoader
     */
    protected $yamlFileLoader;

    /**
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * TagsService constructor.
     */
    public function __construct()
    {
        $this->objectManger = GeneralUtility::makeInstance(ObjectManager::class);
        $this->packageManager = $this->objectManger->get(PackageManager::class);
        $this->yamlFileLoader = $this->objectManger->get(YamlFileLoader::class);
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
                    'path' => 'EXT:' . $extensionName . '/Resources/Private/Language/' . $fileName,
                    'labels' => $labels
                ];
            }
        }

        return $configuration;
    }

    /**
     * @return array
     */
    protected function getGlobalConfiguration(): array
    {
        $configuration = [];
        foreach ($this->getYamlFilesFromPackages() as $file) {
            foreach ($this->readSingleFile($file) as $ext => $files) {
                if ($ext === self::RESERVED_NAME) {
                    continue;
                }
                if (!key_exists($ext, $configuration)) {
                    $configuration[$ext] = [];
                }
                $this->populateRows($configuration[$ext], $files);
            }
        }

        return $configuration;
    }

    /**
     * Merge and create configuration
     * @param array $configuration
     * @param array $files
     */
    protected function populateRows(array &$configuration, array $files): void
    {
        foreach ($files as $langFile => $rows) {
            if (!key_exists($langFile, $configuration)) {
                $configuration[$langFile] = [];
            }
            foreach ($rows as $key => $properties) {
                if (!key_exists($key, $configuration[$langFile])) {
                    $configuration[$langFile][$key] = [];
                }
                foreach ($properties as $property => $values) {
                    if (!key_exists($property, $configuration[$langFile][$key])) {
                        $configuration[$langFile][$key][$property] = $values;
                    } else {
                        $configuration[$langFile][$key][$property] = array_unique(array_merge($configuration[$langFile][$key][$property], $values));
                    }
                }
            }
        }
    }

    /**
     * @param string $file
     * @return array
     */
    protected function readSingleFile(string $file): array
    {
        return $this->yamlFileLoader->load($file, 0);
    }

    /**
     * @return array
     */
    protected function getYamlFilesFromPackages(): array
    {
        $files = [];
        foreach ($this->packageManager->getActivePackages() as $package) {
            $tagsYaml = $package->getPackagePath() . 'Configuration/Translation/FrontendTags.yaml';
            if (@is_file($tagsYaml)) {
                $files[] = $tagsYaml;
            }
        }

        return $files;
    }

}

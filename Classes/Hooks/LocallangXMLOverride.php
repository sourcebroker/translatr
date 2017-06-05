<?php

namespace SourceBroker\Translatr\Hooks;

use SourceBroker\Translatr\Utility\ExceptionUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class LocallangXMLOverride
 *
 * @package SourceBroker\Translatr\Hooks
 */
class LocallangXMLOverride
{
    /**
     * @todo change path for TYPO3 v8
     *
     * @var string
     */
    protected $cachePath
        = PATH_site.'typo3temp'.DIRECTORY_SEPARATOR.'Cache'.DIRECTORY_SEPARATOR
        .'TxTranslatr'.DIRECTORY_SEPARATOR;

    /**
     * @var string
     */
    protected $locallangXMLOverrideFilePath;

    /**
     * @var string
     */
    protected $overrideFilesBaseDirectoryPath;

    /**
     * @var
     */
    protected $overrideFilesExtDirectoryPath;

    /**
     *
     */
    public function initialize()
    {
        $this->setLocallangXMLOverrideFilePath();
        $this->setOverrideFilesBaseDirectoryPath();
        $this->setOverrideFilesExtDirectoryPath();

        $this->createLocallangXMLOverrideFileIfNotExists();

        if (!$this->locallangXMLOverrideFileExists()) {
            ExceptionUtility::throwException(
                \RuntimeException::class,
                'Could not create locallang XML Override file in path '
                .$this->locallangXMLOverrideFilePath.' due to unknown reason.',
                82347523
            );

            return;
        }

        include $this->locallangXMLOverrideFilePath;
    }

    /**
     * @return void
     */
    protected function setLocallangXMLOverrideFilePath()
    {
        $this->locallangXMLOverrideFilePath = $this->cachePath
            .'locallangXMLOverride.php';
    }

    /**
     * @return void
     */
    protected function setOverrideFilesBaseDirectoryPath()
    {
        $this->overrideFilesBaseDirectoryPath = $this->cachePath.'OverrideFiles'
            .DIRECTORY_SEPARATOR;
    }

    /**
     * @return void
     */
    protected function setOverrideFilesExtDirectoryPath()
    {
        $this->overrideFilesExtDirectoryPath = $this->overrideFilesBaseDirectoryPath
            .'ext'.DIRECTORY_SEPARATOR;
    }

    /**
     * @return bool
     */
    protected function locallangXMLOverrideFileExists()
    {
        return file_exists($this->locallangXMLOverrideFilePath);
    }

    /**
     * @return void
     */
    protected function createLocallangXMLOverrideFileIfNotExists()
    {
        if ($this->locallangXMLOverrideFileExists()) {
            return;
        }

        $this->createLocallangXMLOverrideFile();
    }

    /**
     * @return void
     */
    protected function createLocallangXMLOverrideFile()
    {
        $this->createLocallangXMLOverrideFileDirectoryIfNotExists();
        $this->createOverrideFilesDirectories();

        $code = '<?php'.PHP_EOL;
        foreach (
            $this->getTranslationOverrideFiles() as $overriddenFile => $filePath
        ) {
            $code .= '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'locallangXMLOverride\'][\''
                .$overriddenFile.'\'][] = \''.$filePath.'\';'.PHP_EOL;
        }

        if (!file_put_contents($this->locallangXMLOverrideFilePath, $code)) {
            ExceptionUtility::throwException(\RuntimeException::class,
                'Could not write file in '.$this->locallangXMLOverrideFilePath,
                390847534);
        }
    }

    /**
     * @return void
     */
    protected function createLocallangXMLOverrideFileDirectoryIfNotExists()
    {
        $dir = dirname($this->locallangXMLOverrideFilePath);

        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true)) {
                ExceptionUtility::throwException(\RuntimeException::class,
                    'Could not create directory in '.$dir, 938457943);
            }

            GeneralUtility::fixPermissions($dir);
        }
    }

    /**
     * @return void
     */
    protected function createOverrideFilesDirectories()
    {
        $this->createOverrideFilesBaseDirectoryIfNotExists();
        $this->createOverrideFilesExtDirectoryIfNotExists();
    }

    /**
     * @return void
     */
    protected function createOverrideFilesBaseDirectoryIfNotExists()
    {
        $this->createDirectoryIfNotExists($this->overrideFilesBaseDirectoryPath);
    }

    /**
     * @return void
     */
    protected function createOverrideFilesExtDirectoryIfNotExists()
    {
        $this->createDirectoryIfNotExists($this->overrideFilesExtDirectoryPath);
    }

    /**
     * @param string $directoryPath Directory absolute path
     */
    protected function createDirectoryIfNotExists($directoryPath)
    {
        if (!is_dir($directoryPath)) {
            if (!mkdir($directoryPath, 0777, true)) {
                ExceptionUtility::throwException(\RuntimeException::class,
                    'Could not create directory in '.$directoryPath, 938457943);
            }

            GeneralUtility::fixPermissions($directoryPath);
        }
    }

    /**
     * @return array
     *
     * @todo check if return of relative path (in element value path) works fine. It will be better to return relative path to avoid problems with some specific server settings
     */
    protected function getTranslationOverrideFiles()
    {
        $translationOverrideFiles = [];

        $files = new \RegexIterator(
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->overrideFilesBaseDirectoryPath)
            ),
            '/locallang\.xlf|locallang\.xml/',
            \RegexIterator::GET_MATCH
        );

        foreach($files as $fullPath => $file) {
            $replacements = [
                $this->overrideFilesExtDirectoryPath => 'EXT:',
                $this->overrideFilesBaseDirectoryPath => '',
            ];

            $translationOverrideFiles[str_replace(array_keys($replacements), $replacements, $fullPath)] = $fullPath;
        }

        return $translationOverrideFiles;
    }

}
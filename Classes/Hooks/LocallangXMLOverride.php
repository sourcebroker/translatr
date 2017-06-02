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
     *
     */
    public function initialize()
    {
        $this->setLocallangXMLOverrideFilePath();
        $this->setOverrideFilesBaseDirectoryPath();
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
        $this->createOverrideFilesBaseDirectoryIfNotExists();

        $code = "<?php\n";
        foreach (
            $this->getTranslationOverrideFiles() as $overriddenFile => $filePath
        ) {
            $code .= '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'locallangXMLOverride\'][\''
                .$overriddenFile.'\'][3454] = \''.$filePath.'\';';
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
    protected function createOverrideFilesBaseDirectoryIfNotExists()
    {
        if (!is_dir($this->overrideFilesBaseDirectoryPath)) {
            if (!mkdir($this->overrideFilesBaseDirectoryPath, 0777, true)) {
                ExceptionUtility::throwException(\RuntimeException::class,
                    'Could not create directory in '
                    .$this->overrideFilesBaseDirectoryPath, 938457943);
            }

            GeneralUtility::fixPermissions($this->overrideFilesBaseDirectoryPath);
        }
    }

    /**
     * @todo
     */
    protected function getTranslationOverrideFiles()
    {
        return [
            'EXT:news/Resources/Private/Language/locallang.xlf' => $this->overrideFilesBaseDirectoryPath
                .'news/Resources/Private/Language/locallang.xlf',
        ];
    }
}
<?php

namespace SourceBroker\Translatr\Hooks;

use SourceBroker\Translatr\Domain\Model\Dto\EmConfiguration;
use SourceBroker\Translatr\Utility\EmConfigurationUtility;
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
    protected $overrideFilesLoaderFilePath;

    /**
     * @var string
     */
    protected $overrideFilesBaseDirectoryPath;

    /**
     * @var
     */
    protected $overrideFilesExtDirectoryPath;

    /**
     * @var EmConfiguration
     */
    protected $emConfiguration = null;

    /**
     *
     */
    public function initialize()
    {
        $this->setEmConfiguration();

        $this->setOverrideFilesLoaderFilePath();
        $this->setOverrideFilesBaseDirectoryPath();
        $this->setOverrideFilesExtDirectoryPath();

        $this->createNotExistingLocallangOverrideFiles();
        $this->createOverrideFilesLoaderFileIfNotExists();

        if (!$this->overrideFilesLoaderFileExists()) {
            ExceptionUtility::throwException(
                \RuntimeException::class,
                'Could not create locallang XML Override file in path '
                .$this->overrideFilesLoaderFilePath.' due to unknown reason.',
                82347523
            );

            return;
        }

        include $this->overrideFilesLoaderFilePath;
    }

    /**
     * @return void
     */
    protected function setEmConfiguration()
    {
        $this->emConfiguration = EmConfigurationUtility::getSettings();
    }

    /**
     * @return void
     */
    protected function setOverrideFilesLoaderFilePath()
    {
        $this->overrideFilesLoaderFilePath = $this->cachePath
            .'locallangOverrideLoader.php';
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
    protected function overrideFilesLoaderFileExists()
    {
        return file_exists($this->overrideFilesLoaderFilePath);
    }

    /**
     * @return void
     */
    protected function createOverrideFilesLoaderFileIfNotExists()
    {
        if ($this->overrideFilesLoaderFileExists()) {
            return;
        }

        $this->createOverrideFilesLoaderFile();
    }

    /**
     * @return void
     */
    protected function createOverrideFilesLoaderFile()
    {
        $this->createOverrideFilesLoaderFileDirectoryIfNotExists();
        $this->createOverrideFilesDirectories();

        $code = '<?php'.PHP_EOL;
        foreach (
            $this->getTranslationOverrideFiles() as $overriddenFile => $filePath
        ) {
            $code .= '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'locallangXMLOverride\'][\''
                .$overriddenFile.'\'][] = \''.$filePath.'\';'.PHP_EOL;
        }

        if (!file_put_contents($this->overrideFilesLoaderFilePath, $code)) {
            ExceptionUtility::throwException(\RuntimeException::class,
                'Could not write file in '.$this->overrideFilesLoaderFilePath,
                390847534);
        }
    }

    /**
     * @return void
     */
    protected function createOverrideFilesLoaderFileDirectoryIfNotExists()
    {
        $this->createDirectoryIfNotExists(dirname($this->overrideFilesLoaderFilePath));
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
            $translationOverrideFiles[
                $this->transformPathFromLocallangOverridesToLocallang($fullPath)
            ] = $fullPath;
        }

        return $translationOverrideFiles;
    }

    /**
     * @return string
     */
    protected function transformPathFromLocallangOverridesToLocallang($fullPath)
    {
        $replacements = [
            $this->overrideFilesExtDirectoryPath => 'EXT:',
            $this->overrideFilesBaseDirectoryPath => '',
        ];

        return str_replace(array_keys($replacements), $replacements, $fullPath);
    }

    /**
     * @param string $locallangPath
     *
     * @return string
     */
    protected function transformPathFromLocallangToLocallangOverrides($locallangPath)
    {
        if (GeneralUtility::isFirstPartOfStr($locallangPath, 'EXT:')) {
            return str_replace('EXT:', $this->overrideFilesExtDirectoryPath, $locallangPath);
        }

        return $this->overrideFilesBaseDirectoryPath.$locallangPath;
    }

    /**
     * @return void
     */
    protected function createNotExistingLocallangOverrideFiles()
    {
        $locallangFiles = (array)$GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
            'DISTINCT tx_translatr_domain_model_label.ll_file',
            'tx_translatr_domain_model_label',
            'tx_translatr_domain_model_label.deleted = 0 AND tx_translatr_domain_model_label.hidden = 0'
        );

        if (!$locallangFiles) {
            return;
        }

        foreach ($locallangFiles as $locallangFile) {
            $this->createLocallangOverrideFileIfNotExist($locallangFile['ll_file']);
        }
    }

    /**
     * @param string $locallangFile
     */
    protected function createLocallangOverrideFileIfNotExist($locallangFile)
    {
        $locallangOverrideFilePath = $this->transformPathFromLocallangToLocallangOverrides($locallangFile);

        if (!is_file($locallangOverrideFilePath)) {
            $this->createLocallangOverrideFile($locallangFile);
        }
    }

    /**
     * @param string $locallangFile
     */
    protected function createLocallangOverrideFile($locallangFile)
    {
        $labels = $this->getLabelsByLocallangFile($locallangFile);
        $defaultLocallangOverrideFile = $this->transformPathFromLocallangToLocallangOverrides($locallangFile);
        $groupedLabels = [];


        foreach($labels as $label) {
            $groupedLabels[$label['isocode']][] = $label;
        }

        unset($labels);

        foreach ($groupedLabels as $isoCode => $labels) {
            $xml = $this->createXlfFileForLabels($labels);
            $xml->formatOutput = true;

            $outputFiles = [
                $this->prependLocallangFileNameWithIsoCode($defaultLocallangOverrideFile, $isoCode)
            ];

            // for default isocode we save it also to file without isocode in name
            if ($isoCode === $this->emConfiguration->getDefaultLanguageIsoCode()) {
                $outputFiles[] = $defaultLocallangOverrideFile;
            }

            foreach ($outputFiles as $outputFile) {
                $this->createDirectoryIfNotExists(dirname($outputFile));
                file_put_contents($outputFile, $xml->saveXML());
            }
        }
    }

    /**
     * @param array $labels
     *
     * @return \DOMDocument
     */
    protected function createXlfFileForLabels(array $labels)
    {
        $xml = new \DOMDocument('1.0', 'utf-8');
        $root = $xml->createElement('xliff');
        $xml->appendChild($root);
        $root->setAttribute('version', '1.0');

        $file = $xml->createElement('file');
        $root->appendChild($file);
        $file->setAttribute('source-language', 'en');
        $file->setAttribute('datatype', 'plaintext');
        $file->setAttribute('original', 'messages');
        $file->setAttribute('date', (new \DateTime())->format('c'));
        $file->setAttribute('product', ''); // @todo enter $labels[{n}]['extension'] here

        $fileHeader = $xml->createElement('header');
        $file->appendChild($fileHeader);

        $fileBody = $xml->createElement('body');
        $file->appendChild($fileBody);

        foreach ($labels as $label) {
            $transUnit = $xml->createElement('trans-unit');
            $transUnit->setAttribute('id', $label['ukey']);

            $target = $xml->createElement('target');
            $transUnit->appendChild($target);

            $target->appendChild(
                $xml->createCDATASection($label['text'])
            );

            $fileBody->appendChild($transUnit);
        }

        return $xml;
    }

    /**
     * @param string $filePath
     * @param string $isoCode
     *
     * @return string
     */
    protected function prependLocallangFileNameWithIsoCode($filePath, $isoCode)
    {
        $fileName = basename($filePath);
        $dirname = dirname($filePath);
        $fileNameWithIsoCode = ($isoCode ? $isoCode.'.' : '').$fileName;

        return $dirname.DIRECTORY_SEPARATOR.$fileNameWithIsoCode;
    }


    /**
     * @param string $locallangFile
     *
     * @return array
     */
    protected function getLabelsByLocallangFile($locallangFile)
    {
        return (array)$GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
            'label.text,
                IF (label.ukey IS NOT NULL AND label.ukey != "", label.ukey, parent.ukey) AS ukey,
                IF (label.extension IS NOT NULL AND label.extension != "", label.extension, parent.extension) AS extension,
                IF (
                    lang.language_isocode IS NOT NULL AND lang.language_isocode != "",
                    lang.language_isocode, 
                    '.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->emConfiguration->getDefaultLanguageIsoCode(), 'tx_translatr_domain_model_label').'
                ) AS isocode',
            'tx_translatr_domain_model_label AS label 
                LEFT JOIN sys_language AS lang ON (
                    label.sys_language_uid = lang.uid
                )
                LEFT JOIN tx_translatr_domain_model_label AS parent ON (
                    label.l10n_parent = parent.uid
                )',
            'label.deleted = 0 
                AND label.hidden = 0
                AND (
                    label.ll_file = '.$GLOBALS['TYPO3_DB']->fullQuoteStr($locallangFile, 'tx_translatr_domain_model_label').' 
                    OR parent.ll_file = '.$GLOBALS['TYPO3_DB']->fullQuoteStr($locallangFile, 'tx_translatr_domain_model_label').'
                )'
        );
    }
}
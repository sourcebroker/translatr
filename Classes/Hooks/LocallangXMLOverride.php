<?php

namespace SourceBroker\Translatr\Hooks;

use SourceBroker\Translatr\Domain\Model\Dto\EmConfiguration;
use SourceBroker\Translatr\Utility\EmConfigurationUtility;
use SourceBroker\Translatr\Utility\ExceptionUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

/**
 * Class LocallangXMLOverride
 *
 * @package SourceBroker\Translatr\Hooks
 */
class LocallangXMLOverride
{
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
        $this->overrideFilesLoaderFilePath = $this->getTempFolderPath()
            .'locallangOverrideLoader.php';
    }

    /**
     * @return void
     */
    protected function setOverrideFilesBaseDirectoryPath()
    {
        $this->overrideFilesBaseDirectoryPath = $this->getTempFolderPath().'OverrideFiles'
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
        $code = '<?php' . PHP_EOL;
        foreach ($this->getTranslationOverrideFiles() as $isocode => $fileDatas) {
            foreach ($fileDatas as $fileData) {
                $code .= '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'locallangXMLOverride\'][\'' . $isocode . '\'][\''
                    . $fileData['overwritten'] . '\'][] = \'' . $fileData['overwriteWith'] . '\';' . PHP_EOL;
            }
        }

        if (!file_put_contents($this->overrideFilesLoaderFilePath, $code)) {
            ExceptionUtility::throwException(\RuntimeException::class,
                'Could not write file in ' . $this->overrideFilesLoaderFilePath,
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

        foreach ($files as $fullPath => $file) {
            $isoCode = explode('/', substr($fullPath, strlen($this->overrideFilesBaseDirectoryPath)))[1];
            $translationOverrideFiles[$isoCode][] = [
                'overwritten' => $this->transformPathFromLocallangOverridesToLocallang($fullPath),
                'overwriteWith' => $fullPath
            ];
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
        $pathInfo = pathinfo(explode(':', str_replace(array_keys($replacements), $replacements, $fullPath))[1]);
        $dirnameExploded = explode(DIRECTORY_SEPARATOR, $pathInfo['dirname']);
        array_shift($dirnameExploded);
        $pathNoIso = implode(DIRECTORY_SEPARATOR, $dirnameExploded);

        $nameExploded = explode('.', $pathInfo['basename']);
        array_shift($nameExploded);
        $nameNoIso = implode('.', $nameExploded);

        return 'EXT:' . $pathNoIso . '/' . $nameNoIso;
    }

    /**
     * @param string $locallangPath
     *
     * @return string
     */
    protected function transformPathFromLocallangToLocallangOverrides($locallangPath, $isocode)
    {
        if (GeneralUtility::isFirstPartOfStr($locallangPath, 'EXT:')) {
            return str_replace('EXT:', $this->overrideFilesExtDirectoryPath .  $isocode . '/', $locallangPath);
        }
        return $this->overrideFilesBaseDirectoryPath.$locallangPath;
    }

    /**
     * @return void
     */
    protected function createNotExistingLocallangOverrideFiles()
    {
        $locallangFiles = (array)$GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
            'DISTINCT tx_translatr_domain_model_label.ll_file, language',
            'tx_translatr_domain_model_label',
            'tx_translatr_domain_model_label.deleted = 0 AND tx_translatr_domain_model_label.hidden = 0'
        );
        if (!$locallangFiles) {
            return;
        }
        foreach ($locallangFiles as $locallangFile) {
            $this->createLocallangOverrideFileIfNotExist($locallangFile['ll_file'], $locallangFile['language']);
        }
    }

    /**
     * @param string $locallangFile
     * @param $isoCode
     */
    protected function createLocallangOverrideFileIfNotExist($locallangFile, $isoCode)
    {
        $locallangOverrideFilePath = $this->transformPathFromLocallangToLocallangOverrides($locallangFile, $isoCode);
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
        $groupedLabels = [];


        foreach($labels as $label) {
            $groupedLabels[$label['isocode']][] = $label;
        }

        unset($labels);
        foreach ($groupedLabels as $isoCode => $labels) {
            $xml = $this->createXlfFileForLabels($labels);
            $xml->formatOutput = true;
            $defaultLocallangOverrideFile = $this->transformPathFromLocallangToLocallangOverrides($locallangFile, $isoCode);
            $outputFiles = [
                $this->prependLocallangFileNameWithIsoCode($defaultLocallangOverrideFile, $isoCode)
            ];
            foreach ($outputFiles as $outputFile) {
                $this->createDirectoryIfNotExists(dirname($outputFile));
                file_put_contents($outputFile, $xml->saveXML());
                $pathParts = pathinfo($outputFile);
                rename($outputFile, $pathParts['dirname'] . '/' . $pathParts['filename'] . '.xlf');
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

            if($label['isocode'] == 'default') {
                $source = $xml->createElement('source');
                $transUnit->appendChild($source);
                $source->appendChild(
                    $xml->createCDATASection($label['text'])
                );
            } else {
                $target = $xml->createElement('target');
                $transUnit->appendChild($target);
                $target->appendChild(
                    $xml->createCDATASection($label['text'])
                );
            }
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
        return $dirname. '/'. $isoCode . '.' . $fileName;
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
                label.language AS isocode',
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


    /**
     * @return string
     */
    protected function getTempFolderPath()
    {
        if (VersionNumberUtility::convertVersionNumberToInteger(TYPO3_branch) >
            VersionNumberUtility::convertVersionNumberToInteger('8.0.0')
        ) {
            $cachePath = 'var/Cache/Data/TxTranslatr/';

        } else {
            $cachePath = 'Cache/Data/TxTranslatr/';
        }
        $tempFolderPath = PATH_site . 'typo3temp/' . $cachePath;
        if (!is_dir($tempFolderPath)) {
            GeneralUtility::mkdir_deep($tempFolderPath);
        }
        return $tempFolderPath;
    }
}
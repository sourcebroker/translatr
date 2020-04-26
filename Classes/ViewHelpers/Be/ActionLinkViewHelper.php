<?php

namespace SourceBroker\Translatr\ViewHelpers\Be;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentValueException;
use TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList;

/**
 * Class ActionLinkViewHelper
 *
 */
class ActionLinkViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper
{
    const TABLE = 'tx_translatr_domain_model_label';
    const MODULE_NAME = 'web_TranslatrTranslate';

    /**
     * @throws \TYPO3\CMS\Fluid\Core\ViewHelper\Exception
     *
     * @return void
     */
    public function initializeArguments()
    {
        $this->registerArgument(
            'type',
            'string',
            'Type of the action; Possible values: `edit`, `hide`, `show`, `delete`.',
            true
        );
        $this->registerArgument(
            'label',
            'array',
            'Label on which action should be taken.',
            false
        );
        $this->registerArgument(
            'options',
            'array',
            'Additional options.',
            false
        );
    }

    /**
     * @throws InvalidArgumentValueException
     *
     * @return string
     */
    public function render()
    {
        if (!isset($this->arguments['options'])) {
            $this->arguments['options'] = [];
        }

        switch ($this->arguments['type']) {
            case 'new':
                return $this->renderNewLink($this->arguments['options']);
            case 'edit':
                return $this->renderEditLink(
                    $this->arguments['label'],
                    $this->arguments['options']
                );
            default:
                throw new InvalidArgumentValueException(
                    'Unknown action type `'
                    . $this->arguments['type'] . '`.',
                    1982739543
                );
        }
    }

    /**
     * @param array $options
     *
     * @return string
     */
    public function renderNewLink($options)
    {
        $pid = 0;
        $uriParameters = [
            'edit' => [
                self::TABLE => [
                    $pid => 'new',
                ],
            ],
            'returnUrl' => self::getReturnUrl(),
        ];

        if (isset($options['tcadefault'])) {
            $uriParameters['translatr_tcadefault'] = $options['tcadefault'];
        }
        return self::getModuleUrl('record_edit', $uriParameters);
    }

    /**
     * @param array $label
     * @param array $options
     *
     * @return string
     */
    public function renderEditLink(array $label, array $options = [])
    {
        $uriParameters = [
            'edit' => [
                self::TABLE => [
                    $label['uid'] => 'edit',
                ],
            ],
            'returnUrl' => self::getReturnUrl(),
        ];

        return self::getModuleUrl('record_edit', $uriParameters);
    }

    /**
     * @return DatabaseRecordList
     */
    protected static function getDatabaseRecordList()
    {
        return GeneralUtility::makeInstance(DatabaseRecordList::class);
    }

    /**
     * @return string
     */
    protected static function getReturnUrl()
    {
        return self::getThisModuleUrl(self::getCurrentParameters());
    }

    /**
     * @return string
     */
    public static function getThisModuleUrl($urlParameters = [])
    {
        return self::getModuleUrl(self::MODULE_NAME, $urlParameters);
    }

    /**
     * @return array
     */
    public static function getCurrentParameters($getParameters = [])
    {
        if (empty($getParameters)) {
            $getParameters = GeneralUtility::_GET();
        }
        $parameters = [];
        $ignoreKeys = [
            'M',
            'moduleToken',
        ];
        if (is_array($getParameters)) {
            foreach ($getParameters as $key => $value) {
                if (in_array($key, $ignoreKeys)) {
                    continue;
                }
                $parameters[$key] = $value;
            }
        }

        return $parameters;
    }

    public static function getModuleUrl($moduleName, $urlParameters = [])
    {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        try {
            $uri = $uriBuilder->buildUriFromRoute($moduleName, $urlParameters);
        } catch (\TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException $e) {
            $uri = $uriBuilder->buildUriFromRoutePath($moduleName, $urlParameters);
        }
        return (string)$uri;
    }
}

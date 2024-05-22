<?php

namespace SourceBroker\Translatr\ViewHelpers\Be;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
use TYPO3\CMS\Backend\RecordList\DatabaseRecordList;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentValueException;

class ActionLinkViewHelper extends AbstractViewHelper
{
    const TABLE = 'tx_translatr_domain_model_label';
    const MODULE_NAME = 'translatr';

    /**
     * @throws Exception
     *
     */
    public function initializeArguments(): void
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
        );
        $this->registerArgument(
            'options',
            'array',
            'Additional options.',
        );
    }

    /**
     * @throws InvalidArgumentValueException
     *
     */
    public function render(): string
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

    public function renderNewLink(array $options): string
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

    public function renderEditLink(array $label, array $options = []): string
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

    protected static function getDatabaseRecordList(): DatabaseRecordList
    {
        return GeneralUtility::makeInstance(DatabaseRecordList::class);
    }

    protected static function getReturnUrl(): string
    {
        return self::getThisModuleUrl(self::getCurrentParameters());
    }

    public static function getThisModuleUrl($urlParameters = []): string
    {
        return self::getModuleUrl(self::MODULE_NAME, $urlParameters);
    }

    public static function getCurrentParameters($getParameters = []): array
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
        $uri = '';
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        try {
            $uri = (string)$uriBuilder->buildUriFromRoute($moduleName, $urlParameters);
        } catch (RouteNotFoundException $e) {
        }
        return (string)$uri;
    }
}

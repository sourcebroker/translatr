<?php

namespace SourceBroker\Translatr\ViewHelpers\Be;

use TYPO3\CMS\Backend\RecordList\DatabaseRecordList;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentValueException;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;

class ActionLinkViewHelper extends AbstractViewHelper
{
    public const TABLE = 'tx_translatr_domain_model_label';
    public const MODULE_NAME = 'translatr';

    /**
     * @throws Exception
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
     */
    public function render(): string
    {
        $this->arguments['options'] ??= [];

        return match ($this->arguments['type']) {
            'new' => $this->renderNewLink($this->arguments['options']),
            'edit' => $this->renderEditLink(
                $this->arguments['label'],
                $this->arguments['options']
            ),
            default => throw new InvalidArgumentValueException(
                'Unknown action type `'
                . $this->arguments['type'] . '`.',
                1982739543
            ),
        };
    }

    /**
     * @param array<string, mixed> $options
     */
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

    /**
     * @param array<string, mixed> $label
     * @param array<string, mixed> $options
     */
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

    /**
     * @param array<string, mixed> $urlParameters
     */
    public static function getThisModuleUrl(array $urlParameters = []): string
    {
        return self::getModuleUrl(self::MODULE_NAME, $urlParameters);
    }

    /**
     * @param array<string, mixed> $getParameters
     * @return array<string, mixed>
     */
    public static function getCurrentParameters(array $getParameters = []): array
    {
        if ($getParameters === []) {
            $getParameters = $GLOBALS['TYPO3_REQUEST']->getQueryParams();
        }
        $parameters = [];
        $ignoreKeys = [
            'M',
            'moduleToken',
        ];
        foreach ($getParameters as $key => $value) {
            if (in_array($key, $ignoreKeys)) {
                continue;
            }
            $parameters[$key] = $value;
        }

        return $parameters;
    }

    /**
     * @param array<string, mixed> $urlParameters
     */
    public static function getModuleUrl(string $moduleName, array $urlParameters = []): string
    {
        $uri = '';
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        try {
            $uri = (string)$uriBuilder->buildUriFromRoute($moduleName, $urlParameters);
        } catch (RouteNotFoundException) {
        }
        return (string)$uri;
    }
}

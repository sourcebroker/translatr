<?php

namespace SourceBroker\Translatr\ViewHelpers\Be;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentValueException;
use TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList;

/**
 * Class ActionLinkViewHelper
 *
 */
class ActionLinkViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
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
            case 'localize':
                return $this->renderLocalizeLink(
                    $this->arguments['label'],
                    $this->arguments['options']
                );
            case 'localization':
                return $this->renderLocalization($this->arguments['label']);
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
        // currently all records are stored on pid 0
        // $pid = (int)\TYPO3\CMS\Core\Utility\GeneralUtility::_GET('id');
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

        return BackendUtility::getModuleUrl('record_edit', $uriParameters);
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

        return BackendUtility::getModuleUrl('record_edit', $uriParameters);
    }

    /**
     * @param array $label
     * @param array $options
     *
     * @throws InvalidArgumentValueException
     *
     * @return string
     */
    public function renderLocalizeLink(array $label, array $options = [])
    {
        if (!isset($options['sysLanguageUid'])) {
            throw new InvalidArgumentValueException(
                '`sysLanguageUid` is required setting for localize link',
                198237121456
            );
        }

        $targetLanguageUid = intval($options['sysLanguageUid']);
        $beUser = $GLOBALS['BE_USER'];

        $uriParameters = [
            'cmd' => [
                self::TABLE => [
                    $label['uid'] => [
                        'localize' => $targetLanguageUid,
                    ],
                ],
            ],
            'vC' => $beUser->veriCode(),
            'prErr' => 1,
            'uPT' => 1,
            'redirect' => GeneralUtility::getIndpEnv('REQUEST_URI'),
        ];

        return BackendUtility::getModuleUrl('tce_db', $uriParameters);
    }

    /**
     * @return string
     */
    public function renderLocalization(array $label)
    {
        return $this->getLanguageFlag($label['sys_language_uid']);
    }

    /**
     * @param int $sysLanguageUid
     *
     * @return string
     */
    protected function getLanguageFlag($sysLanguageUid)
    {
        $databaseRecordList = self::getDatabaseRecordList();
        $databaseRecordList->initializeLanguages();

        return $databaseRecordList->languageFlag($sysLanguageUid);
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
        return self::getModuleUrl(self::getCurrentParameters());
    }

    /**
     * @return string
     */
    public static function getModuleUrl($urlParameters = [])
    {
        return BackendUtility::getModuleUrl(self::MODULE_NAME, $urlParameters);
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
}

<?php
declare(strict_types=1);

namespace SourceBroker\Translatr\Service;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class BaseService
 * @package SourceBroker\Translatr\Service
 */
abstract class BaseService
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * BaseService constructor.
     */
    public function __construct()
    {
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
    }
}

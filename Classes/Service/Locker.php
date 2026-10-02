<?php

declare(strict_types=1);

namespace SourceBroker\Translatr\Service;

use TYPO3\CMS\Core\Locking\Exception\LockAcquireWouldBlockException;
use TYPO3\CMS\Core\Locking\LockFactory;
use TYPO3\CMS\Core\Locking\LockingStrategyInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Exclusive lock shared by the generation of the locallang override files and the flush of them,
 * so a flush can not remove the files while another process is generating them.
 */
class Locker
{
    protected const TYPE = 'tx_translatr';
    protected const KEY = 'tx_translatr_key';

    protected ?LockingStrategyInterface $accessLock = null;
    protected ?LockingStrategyInterface $lock = null;

    public function acquire(): void
    {
        $lockFactory = GeneralUtility::makeInstance(LockFactory::class);
        $this->accessLock = $lockFactory->createLocker(self::TYPE);

        $this->lock = $lockFactory->createLocker(
            self::KEY,
            LockingStrategyInterface::LOCK_CAPABILITY_EXCLUSIVE | LockingStrategyInterface::LOCK_CAPABILITY_NOBLOCK
        );

        do {
            if (!$this->accessLock->acquire()) {
                throw new \RuntimeException('Could not acquire access lock for "' . self::TYPE . '"".', 1294586098);
            }

            try {
                $locked = $this->lock->acquire(
                    LockingStrategyInterface::LOCK_CAPABILITY_EXCLUSIVE | LockingStrategyInterface::LOCK_CAPABILITY_NOBLOCK
                );
            } catch (LockAcquireWouldBlockException $e) {
                // somebody else has the lock, we keep waiting

                // first release the access lock
                $this->accessLock->release();
                // now lets make a short break (100ms) until we try again, since
                // the generation by the lock owner will take a while anyways
                usleep(100000);
                continue;
            }
            $this->accessLock->release();
            if ($locked) {
                break;
            }
            throw new \RuntimeException('Could not acquire lock for ' . self::KEY . '.', 1460975877);
        } while (true);
    }

    public function release(): void
    {
        if ($this->accessLock === null || $this->lock === null) {
            return;
        }
        if (!$this->accessLock->acquire()) {
            throw new \RuntimeException('Could not acquire access lock for "' . self::TYPE . '"".', 1460975902);
        }

        $this->lock->release();
        $this->lock->destroy();
        $this->lock = null;

        $this->accessLock->release();
        $this->accessLock = null;
    }
}

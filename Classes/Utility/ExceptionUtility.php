<?php

namespace SourceBroker\Translatr\Utility;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ExceptionUtility
{
    public static function throwException(
        $exceptionClassName,
        $errorMessage,
        $errorCode
    ): void {
        if (Environment::getContext()->isProduction()) {
            // @todo add to TYPO3 logs for production context to not break down the site
        } else {
            $exception = GeneralUtility::makeInstance(
                $exceptionClassName,
                $errorMessage,
                $errorCode
            );

            if ($exception instanceof \Throwable) {
                throw $exception;
            } else {
                throw new \RuntimeException(
                    $exceptionClassName
                    . ' is not the instanceof \Exception or \Throwable',
                    9023740239
                );
            }
        }
    }
}

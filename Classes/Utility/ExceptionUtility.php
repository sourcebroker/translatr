<?php

namespace SourceBroker\Translatr\Utility;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ExceptionUtility
{
    /**
     * @param class-string<\Throwable> $exceptionClassName
     */
    public static function throwException(
        string $exceptionClassName,
        string $errorMessage,
        int $errorCode
    ): void {
        if (Environment::getContext()->isProduction()) {
            // @todo add to TYPO3 logs for production context to not break down the site
        } else {
            $exception = GeneralUtility::makeInstance(
                $exceptionClassName,
                $errorMessage,
                $errorCode
            );

            throw $exception;
        }
    }
}

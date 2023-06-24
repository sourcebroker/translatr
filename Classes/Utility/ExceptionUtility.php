<?php

namespace SourceBroker\Translatr\Utility;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ExceptionUtility
 *
 */
class ExceptionUtility
{
    /**
     * Throws an exception or log into the TYPO3 logs if in production context
     *
     * @return void
     *
     * @throws
     */
    public static function throwException(
        $exceptionClassName,
        $errorMessage,
        $errorCode
    ) {
        if (Environment::getContext()->isProduction()) {
            // @todo add to TYPO3 logs for production context to not break down the site
        } else {
            $exception = GeneralUtility::makeInstance(
                $exceptionClassName,
                $errorMessage,
                $errorCode
            );

            if ($exception instanceof \Exception
                || $exception instanceof \Throwable
            ) {
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

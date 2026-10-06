<?php

declare(strict_types=1);

namespace SourceBroker\Translatr\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use SourceBroker\Translatr\Domain\Exception\LanguageFileGenerationException;
use SourceBroker\Translatr\Service\GenerateLanguageFiles as LanguageFileGenerator;
use TYPO3\CMS\Core\Core\Environment;

class GenerateLanguageFiles implements MiddlewareInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly LanguageFileGenerator $languageFileGenerator,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $this->languageFileGenerator->initialize();
        } catch (LanguageFileGenerationException $exception) {
            if (!Environment::getContext()->isProduction()) {
                throw $exception;
            }
            $this->logger?->error('Translatr could not generate language overrides.', ['exception' => $exception]);
            // Reuse a restored publication if available; otherwise TYPO3 uses its normal language resources.
            $this->languageFileGenerator->loadPublishedFiles();
        }
        return $handler->handle($request);
    }

}

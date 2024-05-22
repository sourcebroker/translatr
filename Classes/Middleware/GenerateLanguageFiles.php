<?php
declare(strict_types=1);

namespace SourceBroker\Translatr\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class GenerateLanguageFiles implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        (new \SourceBroker\Translatr\Service\GenerateLanguageFiles)->initialize();
        return $handler->handle($request);
    }

}

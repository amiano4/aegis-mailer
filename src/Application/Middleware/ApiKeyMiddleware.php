<?php

declare(strict_types=1);

namespace Aegis\Application\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

final class ApiKeyMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly string $apiKey)
    {
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $apiKey = $request->getHeaderLine('X-Api-Key');

        if (!hash_equals($this->apiKey, $apiKey)) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(['error' => 'Unauthorized']));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        return $handler->handle($request);
    }
}

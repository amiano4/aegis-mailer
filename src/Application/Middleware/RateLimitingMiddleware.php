<?php

declare(strict_types=1);

namespace Aegis\Application\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final class RateLimitingMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly RateLimiterFactory $rateLimiterFactory)
    {
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $limiter = $this->rateLimiterFactory->create($request->getAttribute('ip_address'));

        if (false === $limiter->consume()->isAccepted()) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(['error' => 'Too Many Requests']));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(429);
        }

        return $handler->handle($request);
    }
}

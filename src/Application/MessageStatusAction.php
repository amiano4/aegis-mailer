<?php

declare(strict_types=1);

namespace Aegis\Application;

use Aegis\Domain\DeliveryTracker;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class MessageStatusAction
{
    public function __construct(
        private DeliveryTracker $deliveryTracker
    ) {}

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $messageId = $args['messageId'] ?? '';
        $queryParams = $request->getQueryParams();
        $date = $queryParams['date'] ?? null;

        if (empty($messageId)) {
            $error = ['error' => 'Message ID is required'];
            $response->getBody()->write(json_encode($error));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        // Validate date format if provided
        if ($date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $error = ['error' => 'Invalid date format. Use YYYY-MM-DD'];
            $response->getBody()->write(json_encode($error));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        try {
            $status = $this->deliveryTracker->getStatus($messageId, $date);
            
            $responseData = [
                'message_id' => $messageId,
                'lookup_date' => $date,
                'timestamp' => date('c'),
                ...$status
            ];

            $response->getBody()->write(json_encode($responseData, JSON_PRETTY_PRINT));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Throwable $e) {
            $error = [
                'error' => 'Failed to retrieve message status',
                'message' => $e->getMessage()
            ];
            
            $response->getBody()->write(json_encode($error));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }
}
<?php

declare(strict_types=1);

namespace Aegis\Application;

use Interop\Queue\Context;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

final class HealthCheckAction
{
    public function __construct(
        private Context $queueContext,
        private LoggerInterface $logger
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => date('c'),
            'version' => '1.0.0',
            'checks' => []
        ];

        // Check queue system
        try {
            $queue = $this->queueContext->createQueue('send-email');
            $health['checks']['queue'] = [
                'status' => 'healthy',
                'message' => 'Queue system operational'
            ];
        } catch (\Throwable $e) {
            $health['status'] = 'unhealthy';
            $health['checks']['queue'] = [
                'status' => 'unhealthy',
                'message' => 'Queue system error: ' . $e->getMessage()
            ];
        }

        // Check if .env is loaded
        $health['checks']['config'] = [
            'status' => isset($_ENV['API_KEY']) ? 'healthy' : 'unhealthy',
            'message' => isset($_ENV['API_KEY']) ? 'Configuration loaded' : 'Missing API_KEY configuration'
        ];

        // Check SMTP configuration
        $smtpConfigured = isset($_ENV['SMTP_HOST']) && isset($_ENV['SMTP_USERNAME']);
        $health['checks']['smtp_config'] = [
            'status' => $smtpConfigured ? 'healthy' : 'warning',
            'message' => $smtpConfigured ? 'SMTP configuration present' : 'SMTP configuration incomplete'
        ];

        // Check log directory
        $logDir = __DIR__ . '/../../var/logs';
        $health['checks']['logging'] = [
            'status' => is_writable($logDir) ? 'healthy' : 'warning',
            'message' => is_writable($logDir) ? 'Log directory writable' : 'Log directory not writable'
        ];

        // Check queue directory
        $queueDir = __DIR__ . '/../../var/queue';
        $health['checks']['queue_storage'] = [
            'status' => is_writable($queueDir) ? 'healthy' : 'unhealthy',
            'message' => is_writable($queueDir) ? 'Queue storage writable' : 'Queue storage not writable'
        ];

        if ($health['status'] === 'unhealthy') {
            $this->logger->warning('Health check failed', $health);
        }

        $response->getBody()->write(json_encode($health, JSON_PRETTY_PRINT));
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($health['status'] === 'healthy' ? 200 : 503);
    }
}
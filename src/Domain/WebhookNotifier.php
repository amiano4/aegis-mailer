<?php

declare(strict_types=1);

namespace Aegis\Domain;

use Psr\Log\LoggerInterface;

final class WebhookNotifier
{
    public function __construct(
        private LoggerInterface $logger,
        private ?string $webhookUrl = null,
        private ?string $webhookSecret = null
    ) {
        $this->webhookUrl = $webhookUrl ?? $_ENV['WEBHOOK_URL'] ?? null;
        $this->webhookSecret = $webhookSecret ?? $_ENV['WEBHOOK_SECRET'] ?? null;
    }

    public function notifySuccess(string $messageId): void
    {
        if (!$this->webhookUrl) {
            return;
        }

        $payload = [
            'message_id' => $messageId,
            'status' => 'sent',
            'timestamp' => date('c')
        ];

        $this->sendWebhook($payload);
    }

    public function notifyFailure(string $messageId, string $error, int $attempts): void
    {
        if (!$this->webhookUrl) {
            return;
        }

        $payload = [
            'message_id' => $messageId,
            'status' => 'failed', 
            'timestamp' => date('c'),
            'error' => $error,
            'attempts' => $attempts
        ];

        $this->sendWebhook($payload);
    }

    private function sendWebhook(array $payload): void
    {
        try {
            $json = json_encode($payload);
            $signature = $this->generateSignature($json);

            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => [
                        'Content-Type: application/json',
                        'User-Agent: Aegis-Mailer/1.0.0',
                        'X-Aegis-Signature: ' . $signature,
                        'Content-Length: ' . strlen($json)
                    ],
                    'content' => $json,
                    'timeout' => 10
                ]
            ]);

            $response = @file_get_contents($this->webhookUrl, false, $context);
            
            if ($response === false) {
                throw new \Exception('Webhook request failed');
            }

            $this->logger->info('Webhook notification sent', [
                'message_id' => $payload['message_id'],
                'status' => $payload['status'],
                'webhook_url' => $this->webhookUrl,
                'response_length' => strlen($response)
            ]);

        } catch (\Throwable $e) {
            $this->logger->warning('Webhook notification failed', [
                'message_id' => $payload['message_id'],
                'status' => $payload['status'],
                'webhook_url' => $this->webhookUrl,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function generateSignature(string $payload): string
    {
        if (!$this->webhookSecret) {
            return '';
        }

        return 'sha256=' . hash_hmac('sha256', $payload, $this->webhookSecret);
    }

    public function isConfigured(): bool
    {
        return !empty($this->webhookUrl);
    }
}
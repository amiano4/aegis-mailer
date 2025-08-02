<?php

declare(strict_types=1);

namespace Aegis\Domain;

use Psr\Log\LoggerInterface;

final class DeliveryTracker
{
    private string $failureDir;

    public function __construct(
        private LoggerInterface $logger,
        string $baseDir = null
    ) {
        $this->failureDir = ($baseDir ?? __DIR__ . '/../../var') . '/delivery/failed';
        
        // Ensure directory exists
        if (!is_dir($this->failureDir)) {
            mkdir($this->failureDir, 0755, true);
        }
    }

    public function recordFailure(string $messageId, string $error, int $attempts = 1): void
    {
        $date = date('Y-m-d');
        $filepath = $this->failureDir . "/{$date}.json";
        
        $failure = [
            'message_id' => $messageId,
            'failed_at' => date('c'),
            'attempts' => $attempts,
            'last_error' => $error
        ];

        $this->addFailureToFile($filepath, $failure);
        
        $this->logger->error('Email delivery failed', [
            'message_id' => $messageId,
            'error' => $error,
            'attempts' => $attempts,
            'date' => $date
        ]);
    }

    public function getStatus(string $messageId, ?string $date = null): array
    {
        if ($date) {
            // Fast path: check specific date
            return $this->checkDateFile($messageId, $date);
        }

        // Slow path: search all failure files
        return $this->searchAllFiles($messageId);
    }

    public function removeSuccess(string $messageId, string $date): void
    {
        $filepath = $this->failureDir . "/{$date}.json";
        
        if (!file_exists($filepath)) {
            return;
        }

        $data = json_decode(file_get_contents($filepath), true);
        if (!$data || !isset($data['failures'])) {
            return;
        }

        // Remove the message from failures (it succeeded on retry)
        $data['failures'] = array_filter(
            $data['failures'], 
            fn($failure) => $failure['message_id'] !== $messageId
        );

        // Re-index array
        $data['failures'] = array_values($data['failures']);

        // Write back or remove file if empty
        if (empty($data['failures'])) {
            unlink($filepath);
        } else {
            file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT));
        }
    }

    private function checkDateFile(string $messageId, string $date): array
    {
        $filepath = $this->failureDir . "/{$date}.json";
        
        if (!file_exists($filepath)) {
            return ['status' => 'sent', 'message' => 'Email delivered successfully'];
        }

        $data = json_decode(file_get_contents($filepath), true);
        if (!$data || !isset($data['failures'])) {
            return ['status' => 'sent', 'message' => 'Email delivered successfully'];
        }

        foreach ($data['failures'] as $failure) {
            if ($failure['message_id'] === $messageId) {
                return [
                    'status' => 'failed',
                    'message' => 'Email delivery failed',
                    'details' => $failure
                ];
            }
        }

        return ['status' => 'sent', 'message' => 'Email delivered successfully'];
    }

    private function searchAllFiles(string $messageId): array
    {
        $files = glob($this->failureDir . '/*.json');
        
        foreach ($files as $filepath) {
            $data = json_decode(file_get_contents($filepath), true);
            if (!$data || !isset($data['failures'])) {
                continue;
            }

            foreach ($data['failures'] as $failure) {
                if ($failure['message_id'] === $messageId) {
                    return [
                        'status' => 'failed',
                        'message' => 'Email delivery failed',
                        'details' => $failure
                    ];
                }
            }
        }

        return ['status' => 'sent', 'message' => 'Email delivered successfully'];
    }

    private function addFailureToFile(string $filepath, array $failure): void
    {
        $data = [
            'date' => date('Y-m-d'),
            'failures' => []
        ];

        if (file_exists($filepath)) {
            $existing = json_decode(file_get_contents($filepath), true);
            if ($existing && isset($existing['failures'])) {
                $data = $existing;
            }
        }

        // Check if message already exists (for retry scenarios)
        $found = false;
        foreach ($data['failures'] as &$existingFailure) {
            if ($existingFailure['message_id'] === $failure['message_id']) {
                $existingFailure = $failure; // Update existing
                $found = true;
                break;
            }
        }

        if (!$found) {
            $data['failures'][] = $failure;
        }

        file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT));
    }

    public function getFailureStats(?string $date = null): array
    {
        if ($date) {
            $filepath = $this->failureDir . "/{$date}.json";
            if (!file_exists($filepath)) {
                return ['date' => $date, 'count' => 0, 'failures' => []];
            }

            $data = json_decode(file_get_contents($filepath), true);
            return [
                'date' => $date,
                'count' => count($data['failures'] ?? []),
                'failures' => $data['failures'] ?? []
            ];
        }

        // Get stats for all dates
        $files = glob($this->failureDir . '/*.json');
        $stats = [];
        
        foreach ($files as $filepath) {
            $filename = basename($filepath, '.json');
            $data = json_decode(file_get_contents($filepath), true);
            $stats[] = [
                'date' => $filename,
                'count' => count($data['failures'] ?? []),
                'failures' => $data['failures'] ?? []
            ];
        }

        return $stats;
    }
}
<?php

declare(strict_types=1);

namespace Aegis\Application;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class StatusAction
{
    public function __invoke(Request $request, Response $response): Response
    {
        $queueDir = __DIR__ . '/../../var/queue';
        $lockFiles = glob($queueDir . '/*.lock');
        $queueFiles = glob($queueDir . '/send-email*');

        // Filter out lock files and empty files from queue files
        $validQueueFiles = [];
        $totalSize = 0;
        
        foreach ($queueFiles as $file) {
            if (!str_ends_with($file, '.lock') && file_exists($file)) {
                $fileSize = filesize($file);
                if ($fileSize > 0) {
                    $validQueueFiles[] = $file;
                    $totalSize += $fileSize;
                }
            }
        }

        // Check for worker processes
        $workerPath = realpath(__DIR__ . '/../../bin/worker');
        $workerOutput = shell_exec("ps aux | grep 'php.*$workerPath' | grep -v grep | awk '{print \$2}'");
        $workerPids = $workerOutput ? array_filter(array_map('intval', explode("\n", trim($workerOutput)))) : [];

        $status = [
            'service' => 'Aegis Mailer',
            'version' => '1.0.0',
            'timestamp' => date('c'),
            'uptime' => $this->getUptime(),
            'queue' => [
                'pending_messages' => count($validQueueFiles),
                'lock_files' => count($lockFiles),
                'total_size_bytes' => $totalSize,
                'total_size_human' => $this->formatBytes($totalSize),
                'directory' => $queueDir
            ],
            'workers' => [
                'count' => count($workerPids),
                'pids' => $workerPids,
                'status' => count($workerPids) > 0 ? 'running' : 'stopped'
            ],
            'environment' => [
                'php_version' => PHP_VERSION,
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
                'memory_limit' => ini_get('memory_limit')
            ]
        ];

        $response->getBody()->write(json_encode($status, JSON_PRETTY_PRINT));
        
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function getUptime(): string
    {
        if (function_exists('uptime')) {
            return uptime();
        }
        
        // Fallback: get system uptime on Unix-like systems
        if (file_exists('/proc/uptime')) {
            $uptime = file_get_contents('/proc/uptime');
            $uptimeSeconds = (int)explode(' ', $uptime)[0];
            
            $days = floor($uptimeSeconds / 86400);
            $hours = floor(($uptimeSeconds % 86400) / 3600);
            $minutes = floor(($uptimeSeconds % 3600) / 60);
            
            return "{$days}d {$hours}h {$minutes}m";
        }
        
        return 'unknown';
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes == 0) return '0 B';

        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
}
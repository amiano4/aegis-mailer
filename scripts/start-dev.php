#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Combined development server and queue worker starter
 */

echo "🚀 Starting Aegis Mailer Development Environment...\n\n";

// Find available port first
function findAvailablePort(int $startPort = 8000): int
{
    for ($port = $startPort; $port <= $startPort + 100; $port++) {
        $socket = @fsockopen('127.0.0.1', $port, $errno, $errstr, 1);
        if ($socket) {
            fclose($socket);
            continue; // Port is in use
        }
        return $port; // Port is available
    }
    
    throw new RuntimeException("No available ports found in range {$startPort}-" . ($startPort + 100));
}

$port = findAvailablePort(8000);
$baseUrl = "http://127.0.0.1:$port";

echo "📡 Starting development server on port $port...\n";
$serverCmd = "php " . __DIR__ . "/start-server.php --port=$port > /dev/null 2>&1 & echo $!";
$serverPid = (int)shell_exec($serverCmd);

if ($serverPid > 0) {
    echo "✅ Development server started (PID: $serverPid)\n";
    
    // Give server time to start
    sleep(2);
    
    // Start the queue worker as daemon
    echo "⚡ Starting queue worker daemon...\n";
    $queueCmd = "php " . __DIR__ . "/../bin/queue start --daemon";
    passthru($queueCmd);
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "🎉 AEGIS MAILER DEVELOPMENT ENVIRONMENT READY!\n";
    echo str_repeat("=", 60) . "\n\n";
    echo "🌐 API BASE URL: \033[1;32m$baseUrl\033[0m\n";
    echo "📡 Server Status: Running (PID: $serverPid)\n";
    echo "⚡ Queue Worker: Running as daemon\n\n";
    echo "📋 API ENDPOINTS:\n";
    echo "  GET  $baseUrl/health       - Health check\n";
    echo "  GET  $baseUrl/status       - System status\n";
    echo "  POST $baseUrl/send         - Send email (requires API key)\n";
    echo "  GET  $baseUrl/send/{id}/status - Check delivery status\n\n";
    echo "🛠️  MANAGEMENT COMMANDS:\n";
    echo "  composer queue status      - Check queue status\n";
    echo "  composer queue stop        - Stop queue worker\n";
    echo "  composer queue logs        - View logs\n\n";
    echo "⏹️  Press Ctrl+C to stop the development environment\n";
    echo str_repeat("-", 60) . "\n\n";
    
    // Keep the script running and handle Ctrl+C
    pcntl_signal(SIGINT, function() use ($serverPid) {
        echo "\n🛑 Stopping development environment...\n";
        
        // Stop server
        if ($serverPid > 0) {
            posix_kill($serverPid, SIGTERM);
            echo "✅ Development server stopped\n";
        }
        
        // Stop queue worker
        $stopCmd = "php " . __DIR__ . "/../bin/queue stop";
        shell_exec($stopCmd);
        echo "✅ Queue worker stopped\n";
        
        echo "👋 Development environment stopped\n";
        exit(0);
    });
    
    // Wait for signals
    while (true) {
        pcntl_signal_dispatch();
        sleep(1);
    }
    
} else {
    echo "❌ Failed to start development server\n";
    exit(1);
}
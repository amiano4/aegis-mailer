#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Smart development server starter
 * Finds available port starting from 8000 and starts PHP dev server
 */

function isPortAvailable(int $port): bool
{
    $socket = @fsockopen('127.0.0.1', $port, $errno, $errstr, 1);
    if ($socket) {
        fclose($socket);
        return false; // Port is in use
    }
    return true; // Port is available
}

function findAvailablePort(int $startPort = 8000): int
{
    for ($port = $startPort; $port <= $startPort + 100; $port++) {
        if (isPortAvailable($port)) {
            return $port;
        }
    }
    
    throw new RuntimeException("No available ports found in range {$startPort}-" . ($startPort + 100));
}

function startServer(): void
{
    echo "🚀 Starting Aegis Mailer development server...\n\n";
    
    try {
        $port = findAvailablePort(8000);
        $host = '127.0.0.1';
        $docRoot = __DIR__ . '/../public';
        
        echo "✅ Found available port: $port\n";
        echo "🌐 Server will be available at: http://$host:$port\n";
        echo "📁 Document root: $docRoot\n\n";
        echo "Press Ctrl+C to stop the server\n";
        echo str_repeat("-", 50) . "\n\n";
        
        // Start PHP development server
        $command = "php -S $host:$port -t " . escapeshellarg($docRoot);
        passthru($command);
        
    } catch (RuntimeException $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
        echo "💡 Try specifying a different port manually: php -S 127.0.0.1:PORT -t public/\n";
        exit(1);
    }
}

// Handle command line arguments
$options = getopt('h', ['help', 'port:']);

if (isset($options['h']) || isset($options['help'])) {
    echo "Aegis Mailer Development Server\n\n";
    echo "Usage: composer start [options]\n";
    echo "   or: php scripts/start-server.php [options]\n\n";
    echo "Options:\n";
    echo "  --port=PORT  Start server on specific port (default: auto-detect from 8000+)\n";
    echo "  -h, --help   Show this help message\n\n";
    echo "Examples:\n";
    echo "  composer start\n";
    echo "  composer start -- --port=8080\n";
    exit(0);
}

if (isset($options['port'])) {
    $port = (int)$options['port'];
    if ($port < 1 || $port > 65535) {
        echo "❌ Error: Port must be between 1 and 65535\n";
        exit(1);
    }
    
    if (!isPortAvailable($port)) {
        echo "❌ Error: Port $port is already in use\n";
        exit(1);
    }
    
    echo "🚀 Starting server on specified port $port...\n\n";
    $command = "php -S 127.0.0.1:$port -t " . escapeshellarg(__DIR__ . '/../public');
    passthru($command);
} else {
    startServer();
}
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

// Clean up any leftover Aegis Mailer processes only
echo "🧹 Cleaning up any leftover Aegis Mailer processes...\n";
$rootDir = dirname(__DIR__);
shell_exec("pkill -f 'php.*" . addslashes($rootDir) . "/scripts/start-server.php' 2>/dev/null");
shell_exec("pkill -f 'php.*" . addslashes($rootDir) . "/bin/worker' 2>/dev/null");
shell_exec("pkill -f 'php.*" . addslashes($rootDir) . "/bin/queue.*start' 2>/dev/null");

$port = findAvailablePort(8000);
$baseUrl = "http://127.0.0.1:$port";

echo "📡 Starting development server on port $port...\n";
$serverCmd = "php " . __DIR__ . "/start-server.php --port=$port > /dev/null 2>&1 & echo $!";
$serverPid = (int)shell_exec($serverCmd);

if ($serverPid > 0) {
    echo "✅ Development server started (PID: $serverPid)\n";
    
    // Give server time to start
    sleep(2);
    
    // Start the queue worker in background
    echo "⚡ Starting queue worker...\n";
    $rootDir = dirname(__DIR__);
    $logFile = $rootDir . "/var/logs/queue.log";
    
    // Ensure log directory exists
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    
    // Start queue worker daemon and get the actual worker PID
    $startOutput = shell_exec("php " . escapeshellarg($rootDir . "/bin/queue") . " start --daemon 2>&1");
    echo $startOutput;
    
    // Get the actual worker PID by checking running processes
    sleep(1); // Give worker time to start
    $queuePid = null;
    $workerScript = $rootDir . "/bin/worker";
    $psOutput = shell_exec("ps aux | grep 'php.*" . addslashes($workerScript) . "' | grep -v grep | awk '{print \$2}'");
    if ($psOutput) {
        $queuePid = (int) trim(explode("\n", trim($psOutput))[0]);
    }
    
    if ($queuePid > 0) {
        echo "✅ Queue worker daemon running (PID: $queuePid)\n";
    } else {
        echo "❌ Queue worker may have failed to start\n";
        $queuePid = 0; // Set to 0 so monitoring won't check it
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "🎉 AEGIS MAILER DEVELOPMENT ENVIRONMENT READY!\n";
    echo str_repeat("=", 60) . "\n\n";
    echo "🌐 API BASE URL: \033[1;32m$baseUrl\033[0m\n";
    echo "📡 Server Status: Running (PID: $serverPid)\n";
    echo "⚡ Queue Worker: Running (PID: $queuePid)\n";
    echo "📝 Queue Logs: $logFile\n\n";
    echo "📋 API ENDPOINTS:\n";
    echo "  GET  $baseUrl/health       - Health check\n";
    echo "  GET  $baseUrl/status       - System status\n";
    echo "  POST $baseUrl/send         - Send email (requires API key)\n";
    echo "  GET  $baseUrl/send/{id}/status - Check delivery status\n\n";
    echo "🛠️  MANAGEMENT COMMANDS:\n";
    echo "  composer queue status      - Check queue status\n";
    echo "  composer queue stop        - Stop queue worker\n";
    echo "  composer queue logs        - View logs\n";
    echo "  tail -f $logFile           - Follow queue logs live\n\n";
    echo "⏹️  Press Ctrl+C to stop the development environment\n";
    echo str_repeat("-", 60) . "\n\n";
    
    // Show initial queue logs if they exist
    if (file_exists($logFile) && filesize($logFile) > 0) {
        echo "📋 Recent Queue Activity:\n";
        echo str_repeat("-", 40) . "\n";
        echo shell_exec("tail -5 " . escapeshellarg($logFile));
        echo str_repeat("-", 40) . "\n\n";
    }
    
    // Function to cleanup all processes and free ports
    $cleanup = function() use ($serverPid, $queuePid, $port) {
        echo "\n🛑 Stopping development environment...\n";
        
        // Stop server gracefully first
        if ($serverPid > 0 && posix_kill($serverPid, 0)) {
            posix_kill($serverPid, SIGTERM);
            sleep(1); // Give it time to stop gracefully
            
            // Force kill if still running
            if (posix_kill($serverPid, 0)) {
                posix_kill($serverPid, SIGKILL);
            }
            echo "✅ Development server stopped\n";
        }
        
        // Stop queue worker gracefully first
        if ($queuePid > 0 && posix_kill($queuePid, 0)) {
            posix_kill($queuePid, SIGTERM);
            sleep(1); // Give it time to stop gracefully
            
            // Force kill if still running
            if (posix_kill($queuePid, 0)) {
                posix_kill($queuePid, SIGKILL);
            }
            echo "✅ Queue worker stopped\n";
        }
        
        // Kill any remaining PHP processes that might be using our port
        $ppid = getmypid();
        shell_exec("pkill -P $ppid 2>/dev/null");
        
        // Kill any remaining Aegis Mailer processes (only our own)
        $rootDir = dirname(__DIR__);
        shell_exec("pkill -f 'php.*" . addslashes($rootDir) . "/scripts/start-server.php' 2>/dev/null");
        shell_exec("pkill -f 'php.*" . addslashes($rootDir) . "/bin/worker' 2>/dev/null");
        shell_exec("pkill -f 'php.*" . addslashes($rootDir) . "/bin/queue.*start' 2>/dev/null");
        
        echo "🧹 All processes cleaned up\n";
        echo "👋 Development environment stopped\n";
        exit(0);
    };
    
    // Handle Ctrl+C
    pcntl_signal(SIGINT, $cleanup);
    pcntl_signal(SIGTERM, $cleanup);
    
    // Monitor processes and cleanup if any crash
    while (true) {
        pcntl_signal_dispatch();
        
        // Check if server process is still running
        if ($serverPid > 0 && !posix_kill($serverPid, 0)) {
            echo "\n❌ Development server crashed! Cleaning up...\n";
            $cleanup();
        }
        
        // Check if queue worker process is still running (only if we have a valid PID)
        if ($queuePid > 0 && !posix_kill($queuePid, 0)) {
            echo "\n❌ Queue worker crashed! Cleaning up...\n";
            $cleanup();
        }
        
        sleep(2); // Check every 2 seconds
    }
    
} else {
    echo "❌ Failed to start development server\n";
    exit(1);
}
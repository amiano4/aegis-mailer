<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use PHPMailService\Config\EmailConfig;
use PHPMailService\Exceptions\EmailException;
use PHPMailService\Services\EmailService;
use PHPMailService\Utils\Logger;

// Load environment configuration
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// CORS and Content-Type headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Initialize configuration and services
try {
    $config = EmailConfig::fromEnvironment();
    $emailService = new EmailService($config);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Service initialization failed',
        'message' => $e->getMessage(),
    ]);
    exit;
}

// Handle different endpoints
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

switch ($path) {
    case '/':
    case '/send':
        handleEmailSending($emailService, $config);

        break;

    case '/health':
        handleHealthCheck($emailService);

        break;

    case '/status':
        handleStatusCheck($emailService);

        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);

        break;
}

/**
 * Handle email sending - CORE FUNCTIONALITY
 */
function handleEmailSending(EmailService $emailService, EmailConfig $config): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed. Only POST requests are accepted.']);

        return;
    }

    try {
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new EmailException('Invalid JSON input: ' . json_last_error_msg());
        }

        if (! $input) {
            throw new EmailException('Empty request body');
        }

        // Optional API key validation
        if ($config->isApiKeyRequired()) {
            $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            if ($apiKey !== $config->getApiKey()) {
                http_response_code(401);
                echo json_encode(['error' => 'Invalid API key']);
                Logger::logSecurityEvent('Invalid API key attempt', ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);

                return;
            }
        }

        // Send email using POST data
        $result = $emailService->sendFromPost($input);

        if ($result->isSuccess()) {
            echo json_encode([
                'success' => true,
                'message' => 'Email sent successfully',
                'data' => $result->toArray(),
            ]);
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $result->getError(),
                'data' => $result->toArray(),
            ]);
        }

    } catch (EmailException $e) {
        http_response_code(400);
        echo json_encode([
            'error' => $e->getMessage(),
            'context' => $e->getContext(),
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Internal server error',
            'message' => $e->getMessage(),
        ]);
        Logger::getInstance()->error('Unexpected error', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}

/**
 * Handle health check endpoint
 */
function handleHealthCheck(EmailService $emailService): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed. Only GET requests are accepted.']);

        return;
    }

    $health = $emailService->getHealthStatus();

    if ($health['status'] === 'healthy') {
        echo json_encode($health);
    } else {
        http_response_code(503);
        echo json_encode($health);
    }
}

/**
 * Handle status endpoint
 */
function handleStatusCheck(EmailService $emailService): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed. Only GET requests are accepted.']);

        return;
    }

    $config = EmailConfig::fromEnvironment();

    echo json_encode([
        'service' => $config->get('app.name'),
        'version' => $config->get('app.version'),
        'status' => 'operational',
        'timestamp' => date('Y-m-d H:i:s'),
        'environment' => $config->get('app.environment'),
        'endpoints' => [
            'POST /' => 'Send email',
            'POST /send' => 'Send email',
            'GET /health' => 'Health check',
            'GET /status' => 'Service status',
        ],
    ]);
}

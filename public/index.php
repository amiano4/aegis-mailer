<?php

declare(strict_types=1);

use Aegis\Application\Middleware\ApiKeyMiddleware;
use Aegis\Application\Middleware\RateLimitingMiddleware;
use Aegis\Application\SendEmailAction;
use DI\ContainerBuilder;
use Slim\Factory\AppFactory;

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Instantiate PHP-DI ContainerBuilder
$containerBuilder = new ContainerBuilder();

// Set up dependencies
$dependencies = require __DIR__ . '/../config/dependencies.php';
$dependencies($containerBuilder);

// Should be disabled in production
if ($_ENV['APP_ENV'] === 'production') {
    $containerBuilder->enableCompilation(__DIR__ . '/../var/cache');
}

// Build PHP-DI Container instance
$container = $containerBuilder->build();

// Instantiate the app
AppFactory::setContainer($container);
$app = AppFactory::create();

// Add global middleware (but not for health/status endpoints)
$app->addBodyParsingMiddleware(); // Add JSON body parsing
$app->addRoutingMiddleware();

// Add Error Handling Middleware
$displayErrorDetails = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
$app->addErrorMiddleware($displayErrorDetails, true, true);

// Define App Routes

// Health and status endpoints (no auth required)
$app->get('/health', \Aegis\Application\HealthCheckAction::class);
$app->get('/status', \Aegis\Application\StatusAction::class);
$app->get('/send/{messageId}/status', \Aegis\Application\MessageStatusAction::class);

// Protected routes with authentication and rate limiting
$app->group('', function ($group) {
    $group->post('/send', SendEmailAction::class);
})->add(RateLimitingMiddleware::class)
  ->add(new ApiKeyMiddleware($_ENV['API_KEY'] ?? ''));

// Run the app
$app->run();

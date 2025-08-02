<?php

declare(strict_types=1);

use Aegis\Application\SendEmailCommandHandler;
use Aegis\Domain\EmailServiceInterface;
use Aegis\Domain\DeliveryTracker;
use Aegis\Domain\WebhookNotifier;
use Aegis\Infrastructure\PHPMailerEmailService;
use DI\ContainerBuilder;
use Enqueue\Fs\FsConnectionFactory;
use Interop\Queue\Context;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use PHPMailer\PHPMailer\PHPMailer;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\CacheStorage;
use function DI\autowire;

return function (ContainerBuilder $containerBuilder) {
    // Define settings first
    $containerBuilder->addDefinitions([
        'settings' => [
            'displayErrorDetails' => true, // Should be false in production
            'logger' => [
                'name' => 'aegis-mailer',
                'path' => __DIR__ . '/../var/logs/app.log',
                'level' => Monolog\Logger::DEBUG,
            ],
        ],
    ]);

    // Then add other definitions
    $containerBuilder->addDefinitions([
        LoggerInterface::class => function (ContainerInterface $c) {
            $settings = $c->get('settings');

            $loggerSettings = $settings['logger'];
            $logger = new Logger($loggerSettings['name']);

            $processor = new UidProcessor();
            $logger->pushProcessor($processor);

            $handler = new StreamHandler($loggerSettings['path'], $loggerSettings['level']);
            $logger->pushHandler($handler);

            return $logger;
        },

        Context::class => function (ContainerInterface $c) {
            $connectionFactory = new FsConnectionFactory([
                'path' => __DIR__ . '/../var/queue',
            ]);

            return $connectionFactory->createContext();
        },

        RateLimiterFactory::class => function (ContainerInterface $c) {
            $storage = new CacheStorage(
                new FilesystemAdapter(
                    '',
                    0,
                    __DIR__ . '/../var/cache'
                )
            );

            return new RateLimiterFactory([
                'id' => 'aegis-mailer',
                'policy' => 'fixed_window',
                'limit' => (int)($_ENV['RATE_LIMIT'] ?? 100),
                'interval' => '1 hour',
            ], $storage);
        },

        PHPMailer::class => function (ContainerInterface $c) {
            $mailer = new PHPMailer(true);
            // Configure PHPMailer from .env or a config file
            $mailer->isSMTP();
            $mailer->Host = $_ENV['SMTP_HOST'] ?? 'localhost';
            $mailer->Port = (int)($_ENV['SMTP_PORT'] ?? 587);
            $mailer->SMTPAuth = (bool)($_ENV['SMTP_AUTH'] ?? true);
            $mailer->Username = $_ENV['SMTP_USERNAME'] ?? '';
            $mailer->Password = $_ENV['SMTP_PASSWORD'] ?? '';
            $mailer->SMTPSecure = $_ENV['SMTP_ENCRYPTION'] ?? PHPMailer::ENCRYPTION_STARTTLS;
            $mailer->setFrom($_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@example.com', $_ENV['MAIL_FROM_NAME'] ?? 'Aegis Mailer');
            
            // UTF-8 encoding settings
            $mailer->CharSet = 'UTF-8';
            $mailer->Encoding = 'base64'; // Better for UTF-8 content
            $mailer->ContentType = 'text/html; charset=UTF-8';

            return $mailer;
        },

        HTMLPurifier::class => function () {
            $config = HTMLPurifier_Config::createDefault();
            
            // Basic email-safe configuration
            $config->set('Core.Encoding', 'UTF-8');
            $config->set('HTML.Doctype', 'HTML 4.01 Transitional');
            
            // Create cache directory
            $cacheDir = __DIR__ . '/../var/cache/htmlpurifier';
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0755, true);
            }
            $config->set('Cache.SerializerPath', $cacheDir);
            
            // Trusted mode for React Email compatibility
            $config->set('HTML.Trusted', true);
            $config->set('CSS.Trusted', true);
            
            // Security filtering
            $config->set('HTML.ForbiddenElements', 'script,object,embed,applet,form');
            $config->set('HTML.ForbiddenAttributes', 'onclick,onload,onerror,onmouseover,onfocus,onblur,onkeyup,onkeydown,onkeypress,onmouseup,onmousedown,srcset,sizes');
            
            // Email-specific settings
            $config->set('Core.ConvertDocumentToFragment', true);
            $config->set('Output.TidyFormat', false);
            $config->set('Core.NormalizeNewlines', false);
            $config->set('HTML.TidyLevel', 'none');
            $config->set('AutoFormat.AutoParagraph', false);
            $config->set('AutoFormat.Linkify', false);
            $config->set('AutoFormat.RemoveEmpty', false);
            $config->set('Core.CollectErrors', false);
            
            return new HTMLPurifier($config);
        },

        DeliveryTracker::class => function (ContainerInterface $c) {
            return new DeliveryTracker($c->get(LoggerInterface::class));
        },

        WebhookNotifier::class => function (ContainerInterface $c) {
            return new WebhookNotifier($c->get(LoggerInterface::class));
        },

        EmailServiceInterface::class => function (ContainerInterface $c) {
            return new PHPMailerEmailService(
                $c->get(PHPMailer::class),
                $c->get(LoggerInterface::class),
                $c->get(HTMLPurifier::class),
                $c->get(DeliveryTracker::class),
                $c->get(WebhookNotifier::class)
            );
        },

        SendEmailCommandHandler::class => autowire(SendEmailCommandHandler::class),
    ]);
};

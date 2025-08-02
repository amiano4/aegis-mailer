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

            return $mailer;
        },

        HTMLPurifier::class => function () {
            $config = HTMLPurifier_Config::createDefault();
            
            // Essential UTF-8 configuration
            $config->set('Core.Encoding', 'UTF-8');
            $config->set('HTML.Doctype', 'HTML 4.01 Transitional');
            
            // Create cache directory if it doesn't exist
            $cacheDir = __DIR__ . '/../var/cache/htmlpurifier';
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0755, true);
            }
            $config->set('Cache.SerializerPath', $cacheDir);
            
            // Email-friendly HTML elements and attributes
            $config->set('HTML.Allowed', 
                'h1,h2,h3,h4,h5,h6,p,br,strong,b,em,i,u,strike,del,ins,' .
                'ul,ol,li,blockquote,pre,code,' .
                'a[href|title],img[src|alt|width|height|title],' .
                'table,thead,tbody,tfoot,tr,td[colspan|rowspan],th[colspan|rowspan],' .
                'div[style],span[style],font[color|size|face]'
            );
            
            // Allow email-safe CSS properties
            $config->set('CSS.AllowedProperties', 
                'font,font-family,font-size,font-weight,font-style,font-variant,' .
                'color,background,background-color,background-image,' .
                'margin,margin-top,margin-right,margin-bottom,margin-left,' .
                'padding,padding-top,padding-right,padding-bottom,padding-left,' .
                'border,border-top,border-right,border-bottom,border-left,' .
                'border-color,border-style,border-width,border-radius,' .
                'text-align,text-decoration,text-indent,text-transform,' .
                'line-height,letter-spacing,word-spacing,' .
                'width,height,max-width,max-height,min-width,min-height,' .
                'display,visibility,float,clear,position,top,right,bottom,left,' .
                'vertical-align,white-space'
            );
            
            // Unicode and entity handling
            $config->set('Core.EscapeInvalidTags', true);
            $config->set('Core.EscapeInvalidChildren', true);
            $config->set('Core.ConvertDocumentToFragment', true);
            $config->set('Core.CollectErrors', false);
            
            // Allow some HTML5 elements commonly used in emails
            $config->set('HTML.DefinitionID', 'email-html-def');
            $config->set('HTML.DefinitionRev', 1);
            if ($def = $config->maybeGetRawHTMLDefinition()) {
                // Add HTML5 elements
                $def->addElement('section', 'Block', 'Flow', 'Common');
                $def->addElement('article', 'Block', 'Flow', 'Common');
                $def->addElement('header', 'Block', 'Flow', 'Common');
                $def->addElement('footer', 'Block', 'Flow', 'Common');
            }
            
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

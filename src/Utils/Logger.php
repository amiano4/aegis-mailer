<?php

declare(strict_types=1);

namespace PHPMailService\Utils;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonologLogger;
use PHPMailService\Config\EmailConfig;
use Psr\Log\LoggerInterface;

/**
 * Logger Utility
 *
 * Provides structured logging for the email service
 */
class Logger
{
    private static ?LoggerInterface $instance = null;

    public static function getInstance(EmailConfig $config = null): LoggerInterface
    {
        if (self::$instance === null) {
            $config ??= EmailConfig::getInstance();
            self::$instance = self::createLogger($config);
        }

        return self::$instance;
    }

    private static function createLogger(EmailConfig $config): LoggerInterface
    {
        $logger = new MonologLogger('email-service');

        if (! $config->isLoggingEnabled()) {
            return $logger;
        }

        $level = match ($config->get('logging.level', 'info')) {
            'debug' => MonologLogger::DEBUG,
            'info' => MonologLogger::INFO,
            'warning' => MonologLogger::WARNING,
            'error' => MonologLogger::ERROR,
            'critical' => MonologLogger::CRITICAL,
            default => MonologLogger::INFO,
        };

        // File handler with rotation
        $logPath = $config->get('logging.path');
        $handler = new RotatingFileHandler($logPath, 7, $level);

        // Custom formatter
        $formatter = new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context%\n",
            'Y-m-d H:i:s'
        );
        $handler->setFormatter($formatter);

        $logger->pushHandler($handler);

        // Console handler for development
        if (php_sapi_name() === 'cli') {
            $consoleHandler = new StreamHandler('php://stdout', $level);
            $consoleHandler->setFormatter($formatter);
            $logger->pushHandler($consoleHandler);
        }

        return $logger;
    }

    /**
     * Log email sending attempt
     */
    public static function logEmailSent(string $messageId, string $to, string $subject): void
    {
        self::getInstance()->info('Email sent successfully', [
            'message_id' => $messageId,
            'to' => $to,
            'subject' => $subject,
            'timestamp' => time(),
        ]);
    }

    /**
     * Log email sending failure
     */
    public static function logEmailFailed(string $to, string $subject, string $error): void
    {
        self::getInstance()->error('Email sending failed', [
            'to' => $to,
            'subject' => $subject,
            'error' => $error,
            'timestamp' => time(),
        ]);
    }

    /**
     * Log rate limiting event
     */
    public static function logRateLimited(string $identifier, int $attempts): void
    {
        self::getInstance()->warning('Rate limit exceeded', [
            'identifier' => $identifier,
            'attempts' => $attempts,
            'timestamp' => time(),
        ]);
    }

    /**
     * Log security event
     */
    public static function logSecurityEvent(string $event, array $context = []): void
    {
        self::getInstance()->warning('Security event', array_merge([
            'event' => $event,
            'timestamp' => time(),
        ], $context));
    }

    /**
     * Log system event
     */
    public static function logSystemEvent(string $event, array $context = []): void
    {
        self::getInstance()->info('System event', array_merge([
            'event' => $event,
            'timestamp' => time(),
        ], $context));
    }
}

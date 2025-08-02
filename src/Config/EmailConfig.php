<?php

declare(strict_types=1);

namespace PHPMailService\Config;

use InvalidArgumentException;

/**
 * Email Configuration Manager
 *
 * Manages email service configuration with validation and environment support
 */
class EmailConfig
{
    private array $config;
    private static ?self $instance = null;

    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->getDefaultConfig(), $config);
        $this->validateConfig();
    }

    public static function getInstance(array $config = []): self
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    public static function fromEnvironment(): self
    {
        // Centralized environment variable loading with security and validation
        $config = [
            'smtp' => [
                'host' => self::getEnvString('SMTP_HOST', 'localhost'),
                'port' => self::getEnvInt('SMTP_PORT', 587, 1, 65535),
                'username' => self::getEnvString('SMTP_USERNAME'),
                'password' => self::getEnvString('SMTP_PASSWORD'),
                'encryption' => self::getEnvChoice('SMTP_ENCRYPTION', 'tls', ['tls', 'ssl', '']),
                'timeout' => self::getEnvInt('SMTP_TIMEOUT', 30, 1, 300),
                'auth' => self::getEnvBool('SMTP_AUTH', true),
            ],
            'from' => [
                'address' => self::getEnvEmail('MAIL_FROM_ADDRESS', 'noreply@example.com'),
                'name' => self::getEnvString('MAIL_FROM_NAME', 'PHPMail Service'),
            ],
            'rate_limiting' => [
                'enabled' => self::getEnvBool('RATE_LIMITING_ENABLED', true),
                'max_attempts' => self::getEnvInt('RATE_LIMITING_MAX_ATTEMPTS', 100, 1, 10000),
                'time_window' => self::getEnvInt('RATE_LIMITING_TIME_WINDOW', 3600, 60, 86400),
            ],
            'queue' => [
                'enabled' => self::getEnvBool('QUEUE_ENABLED', false),
                'driver' => self::getEnvChoice('QUEUE_DRIVER', 'memory', ['memory', 'redis', 'database']),
                'connection' => self::getEnvString('QUEUE_CONNECTION'),
            ],
            'logging' => [
                'enabled' => self::getEnvBool('LOGGING_ENABLED', true),
                'level' => self::getEnvChoice('LOG_LEVEL', 'info', ['debug', 'info', 'warning', 'error', 'critical']),
                'path' => self::getEnvPath('LOG_PATH', __DIR__ . '/../../logs/email.log'),
            ],
            'security' => [
                'api_key_required' => self::getEnvBool('API_KEY_REQUIRED', false),
                'api_key' => self::getEnvString('API_KEY'),
                'allowed_domains' => self::getEnvArray('ALLOWED_DOMAINS'),
                'max_attachment_size' => self::getEnvInt('MAX_ATTACHMENT_SIZE', 25000000, 1000, 100000000),
                'rate_limit_by_ip' => self::getEnvBool('RATE_LIMIT_BY_IP', true),
                'trusted_proxies' => self::getEnvArray('TRUSTED_PROXIES'),
            ],
            'features' => [
                'templates_enabled' => self::getEnvBool('TEMPLATES_ENABLED', true),
                'attachments_enabled' => self::getEnvBool('ATTACHMENTS_ENABLED', true),
                'batch_sending_enabled' => self::getEnvBool('BATCH_SENDING_ENABLED', true),
                'webhook_enabled' => self::getEnvBool('WEBHOOK_ENABLED', false),
                'webhook_url' => self::getEnvUrl('WEBHOOK_URL'),
                'delivery_confirmation' => self::getEnvBool('DELIVERY_CONFIRMATION', false),
            ],
            'app' => [
                'name' => self::getEnvString('APP_NAME', 'PHPMail Service'),
                'version' => self::getEnvString('APP_VERSION', '1.0.0'),
                'environment' => self::getEnvChoice('APP_ENV', 'production', ['development', 'testing', 'staging', 'production']),
                'debug' => self::getEnvBool('APP_DEBUG', false),
                'timezone' => self::getEnvString('APP_TIMEZONE', 'UTC'),
            ],
        ];

        return new self($config);
    }

    /**
     * Secure environment variable getters with validation
     */
    private static function getEnvString(string $key, string $default = ''): string
    {
        $value = $_ENV[$key] ?? $default;

        return is_string($value) ? trim($value) : $default;
    }

    private static function getEnvInt(string $key, int $default = 0, int $min = PHP_INT_MIN, int $max = PHP_INT_MAX): int
    {
        $value = $_ENV[$key] ?? $default;
        $int = filter_var($value, FILTER_VALIDATE_INT);

        if ($int === false || $int < $min || $int > $max) {
            return $default;
        }

        return $int;
    }

    private static function getEnvBool(string $key, bool $default = false): bool
    {
        $value = $_ENV[$key] ?? ($default ? 'true' : 'false');

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private static function getEnvEmail(string $key, string $default = ''): string
    {
        $value = self::getEnvString($key, $default);

        return filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : $default;
    }

    private static function getEnvUrl(string $key, string $default = ''): string
    {
        $value = self::getEnvString($key, $default);

        return filter_var($value, FILTER_VALIDATE_URL) ? $value : $default;
    }

    private static function getEnvChoice(string $key, string $default, array $choices): string
    {
        $value = self::getEnvString($key, $default);

        return in_array($value, $choices, true) ? $value : $default;
    }

    private static function getEnvArray(string $key, array $default = []): array
    {
        $value = self::getEnvString($key);
        if (empty($value)) {
            return $default;
        }

        return array_filter(array_map('trim', explode(',', $value)));
    }

    private static function getEnvPath(string $key, string $default = ''): string
    {
        $value = self::getEnvString($key, $default);

        if (empty($value)) {
            return $default;
        }

        // Ensure directory exists
        $dir = dirname($value);
        if (! empty($dir) && ! is_dir($dir)) {
            @mkdir($dir, 0o755, true);
        }

        return $value;
    }

    private function getDefaultConfig(): array
    {
        return [
            'smtp' => [
                'host' => 'localhost',
                'port' => 587,
                'username' => '',
                'password' => '',
                'encryption' => 'tls',
                'timeout' => 30,
                'auth' => true,
            ],
            'from' => [
                'address' => 'noreply@example.com',
                'name' => 'PHPMail Service',
            ],
            'rate_limiting' => [
                'enabled' => true,
                'max_attempts' => 100,
                'time_window' => 3600,
            ],
            'queue' => [
                'enabled' => false,
                'driver' => 'memory',
                'connection' => '',
            ],
            'logging' => [
                'enabled' => true,
                'level' => 'info',
                'path' => __DIR__ . '/../../logs/email.log',
            ],
            'security' => [
                'api_key_required' => false,
                'api_key' => '',
                'allowed_domains' => [],
                'max_attachment_size' => 25000000,
                'rate_limit_by_ip' => true,
                'trusted_proxies' => [],
            ],
            'features' => [
                'templates_enabled' => true,
                'attachments_enabled' => true,
                'batch_sending_enabled' => true,
                'webhook_enabled' => false,
                'webhook_url' => '',
                'delivery_confirmation' => false,
            ],
            'app' => [
                'name' => 'PHPMail Service',
                'version' => '1.0.0',
                'environment' => 'production',
                'debug' => false,
                'timezone' => 'UTC',
            ],
        ];
    }

    private function validateConfig(): void
    {
        // Validate SMTP configuration
        if (empty($this->config['smtp']['host'])) {
            throw new InvalidArgumentException('SMTP host is required');
        }

        if (! in_array($this->config['smtp']['encryption'], ['tls', 'ssl', ''])) {
            throw new InvalidArgumentException('SMTP encryption must be "tls", "ssl", or empty');
        }

        // Validate from address
        if (! filter_var($this->config['from']['address'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid from email address');
        }

        // Validate rate limiting
        if ($this->config['rate_limiting']['max_attempts'] < 1) {
            throw new InvalidArgumentException('Rate limiting max attempts must be greater than 0');
        }

        // Validate logging path
        $logDir = dirname($this->config['logging']['path']);
        if (! is_dir($logDir) && ! mkdir($logDir, 0o755, true)) {
            throw new InvalidArgumentException("Cannot create log directory: $logDir");
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (! is_array($value) || ! array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    public function set(string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $config = &$this->config;

        foreach ($keys as $k) {
            if (! isset($config[$k]) || ! is_array($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }

        $config = $value;
    }

    public function getAll(): array
    {
        return $this->config;
    }

    public function getSmtpConfig(): array
    {
        return $this->config['smtp'];
    }

    public function getFromConfig(): array
    {
        return $this->config['from'];
    }

    public function isRateLimitingEnabled(): bool
    {
        return $this->config['rate_limiting']['enabled'];
    }

    public function isQueueEnabled(): bool
    {
        return $this->config['queue']['enabled'];
    }

    public function isLoggingEnabled(): bool
    {
        return $this->config['logging']['enabled'];
    }

    public function isApiKeyRequired(): bool
    {
        return $this->config['security']['api_key_required'];
    }

    public function getApiKey(): string
    {
        return $this->config['security']['api_key'];
    }

    public function getAllowedDomains(): array
    {
        return $this->config['security']['allowed_domains'];
    }

    public function isFeatureEnabled(string $feature): bool
    {
        return $this->config['features'][$feature] ?? false;
    }
}

<?php

declare(strict_types=1);

namespace PHPMailService\Tests\Unit\Config;

use InvalidArgumentException;
use PHPMailService\Config\EmailConfig;
use PHPUnit\Framework\TestCase;

class EmailConfigTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset environment variables and singleton for clean testing
        $_ENV = [];
        EmailConfig::resetInstance();
    }

    public function testCanCreateConfigWithDefaults(): void
    {
        $config = new EmailConfig([]);

        $this->assertEquals('localhost', $config->get('smtp.host'));
        $this->assertEquals(587, $config->get('smtp.port'));
        $this->assertEquals('noreply@example.com', $config->get('from.address'));
        $this->assertTrue($config->isLoggingEnabled());
        $this->assertFalse($config->isApiKeyRequired());
    }

    public function testCanCreateConfigFromEnvironment(): void
    {
        $_ENV['SMTP_HOST'] = 'smtp.test.com';
        $_ENV['SMTP_PORT'] = '465';
        $_ENV['MAIL_FROM_ADDRESS'] = 'test@test.com';
        $_ENV['API_KEY_REQUIRED'] = 'true';
        $_ENV['API_KEY'] = 'secret-key';

        $config = EmailConfig::fromEnvironment();

        $this->assertEquals('smtp.test.com', $config->get('smtp.host'));
        $this->assertEquals(465, $config->get('smtp.port'));
        $this->assertEquals('test@test.com', $config->get('from.address'));
        $this->assertTrue($config->isApiKeyRequired());
        $this->assertEquals('secret-key', $config->getApiKey());
    }

    public function testValidatesInvalidEmailAddress(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid from email address');

        new EmailConfig([
            'from' => [
                'address' => 'invalid-email',
            ],
        ]);
    }

    public function testValidatesInvalidEncryption(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('SMTP encryption must be "tls", "ssl", or empty');

        new EmailConfig([
            'smtp' => [
                'host' => 'smtp.test.com',
                'encryption' => 'invalid',
            ],
        ]);
    }

    public function testValidatesEmptySmtpHost(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('SMTP host is required');

        new EmailConfig([
            'smtp' => [
                'host' => '',
            ],
        ]);
    }

    public function testEnvironmentVariableValidation(): void
    {
        $_ENV['SMTP_PORT'] = '999999'; // Invalid port
        $_ENV['MAIL_FROM_ADDRESS'] = 'invalid-email'; // Invalid email
        $_ENV['LOG_LEVEL'] = 'invalid'; // Invalid log level

        $config = EmailConfig::fromEnvironment();

        // Should fall back to defaults for invalid values
        $this->assertEquals(587, $config->get('smtp.port')); // Default port
        $this->assertEquals('noreply@example.com', $config->get('from.address')); // Default email
        $this->assertEquals('info', $config->get('logging.level')); // Default log level
    }

    public function testGetMethod(): void
    {
        $config = new EmailConfig([
            'test' => [
                'nested' => [
                    'value' => 'test-value',
                ],
            ],
        ]);

        $this->assertEquals('test-value', $config->get('test.nested.value'));
        $this->assertEquals('default', $config->get('non.existent.key', 'default'));
        $this->assertNull($config->get('non.existent.key'));
    }

    public function testSetMethod(): void
    {
        $config = new EmailConfig([]);

        $config->set('new.config.value', 'test');

        $this->assertEquals('test', $config->get('new.config.value'));
    }

    public function testFeatureFlags(): void
    {
        $config = new EmailConfig([
            'features' => [
                'templates_enabled' => true,
                'attachments_enabled' => false,
                'webhook_enabled' => true,
            ],
        ]);

        $this->assertTrue($config->isFeatureEnabled('templates_enabled'));
        $this->assertFalse($config->isFeatureEnabled('attachments_enabled'));
        $this->assertTrue($config->isFeatureEnabled('webhook_enabled'));
        $this->assertFalse($config->isFeatureEnabled('non_existent_feature'));
    }

    public function testRateLimitingConfig(): void
    {
        $_ENV['RATE_LIMITING_ENABLED'] = 'true';
        $_ENV['RATE_LIMITING_MAX_ATTEMPTS'] = '50';
        $_ENV['RATE_LIMITING_TIME_WINDOW'] = '1800';

        $config = EmailConfig::fromEnvironment();

        $this->assertTrue($config->isRateLimitingEnabled());
        $this->assertEquals(50, $config->get('rate_limiting.max_attempts'));
        $this->assertEquals(1800, $config->get('rate_limiting.time_window'));
    }

    public function testAllowedDomains(): void
    {
        $_ENV['ALLOWED_DOMAINS'] = 'example.com,test.com,trusted.org';

        $config = EmailConfig::fromEnvironment();
        $allowedDomains = $config->getAllowedDomains();

        $this->assertCount(3, $allowedDomains);
        $this->assertContains('example.com', $allowedDomains);
        $this->assertContains('test.com', $allowedDomains);
        $this->assertContains('trusted.org', $allowedDomains);
    }
}

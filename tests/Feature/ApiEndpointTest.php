<?php

declare(strict_types=1);

namespace PHPMailService\Tests\Feature;

use PHPUnit\Framework\TestCase;

class ApiEndpointTest extends TestCase
{
    protected function setUp(): void
    {
        // Set test environment variables
        $_ENV['APP_ENV'] = 'testing';
        $_ENV['LOGGING_ENABLED'] = 'false';
        $_ENV['SMTP_HOST'] = 'localhost';
        $_ENV['SMTP_PORT'] = '1025';
        $_ENV['SMTP_AUTH'] = 'false';
        $_ENV['MAIL_FROM_ADDRESS'] = 'test@example.com';
    }

    public function testHealthEndpointReturnsJson(): void
    {
        $this->markTestSkipped('Requires running server for feature tests');
    }

    public function testStatusEndpointReturnsServiceInfo(): void
    {
        $this->markTestSkipped('Requires running server for feature tests');
    }

    public function testSendEmailEndpointAcceptsValidRequest(): void
    {
        $this->markTestSkipped('Requires running server and mail server for feature tests');
    }

    public function testSendEmailEndpointRejectsInvalidRequest(): void
    {
        $this->markTestSkipped('Requires running server for feature tests');
    }

    public function testSendEmailEndpointRejectsGetRequest(): void
    {
        $this->markTestSkipped('Requires running server for feature tests');
    }

    public function testApiKeyAuthenticationWhenRequired(): void
    {
        $this->markTestSkipped('Requires running server with API key configuration');
    }

    public function testCorsHeadersArePresent(): void
    {
        $this->markTestSkipped('Requires running server for feature tests');
    }

    // Helper methods can be restored when implementing actual feature tests
    // that connect to a running server instance for end-to-end testing
}
<?php

declare(strict_types=1);

namespace PHPMailService\Tests\Integration;

use PHPMailService\Config\EmailConfig;
use PHPMailService\Models\EmailMessage;
use PHPMailService\Services\EmailService;
use PHPUnit\Framework\TestCase;

class EmailServiceIntegrationTest extends TestCase
{
    private EmailService $emailService;
    private EmailConfig $config;

    protected function setUp(): void
    {
        // Use test configuration
        $this->config = new EmailConfig([
            'smtp' => [
                'host' => 'localhost',
                'port' => 1025, // Mailhog test server port
                'username' => 'test@example.com',
                'password' => 'password',
                'encryption' => '',
                'auth' => false,
            ],
            'from' => [
                'address' => 'test@example.com',
                'name' => 'Test Service',
            ],
            'logging' => [
                'enabled' => false, // Disable logging in tests
                'path' => '/tmp/test-email.log', // Explicit path for tests
            ],
        ]);

        $this->emailService = new EmailService($this->config);
    }

    public function testCanSendBasicEmail(): void
    {
        $message = new EmailMessage(
            'recipient@example.com',
            'Integration Test Email',
            'This is a test email from the integration test suite.'
        );

        // This test will only pass if you have a local mail server running (like Mailhog)
        // Skip if not available
        if (! $this->isMailServerAvailable()) {
            $this->markTestSkipped('Mail server not available for integration testing');
        }

        $result = $this->emailService->send($message);

        $this->assertTrue($result->isSuccess());
        $this->assertNotEmpty($result->getMessageId());
        $this->assertEquals($message->getId(), $result->getEmailId());
    }

    public function testCanSendHtmlEmail(): void
    {
        if (! $this->isMailServerAvailable()) {
            $this->markTestSkipped('Mail server not available for integration testing');
        }

        $message = new EmailMessage(
            'recipient@example.com',
            'HTML Integration Test',
            '<h1>HTML Test</h1><p>This is an <strong>HTML</strong> email.</p>'
        );
        $message->setBody($message->getBody(), true);
        $message->setTextBody('HTML Test - This is an HTML email.');

        $result = $this->emailService->send($message);

        $this->assertTrue($result->isSuccess());
    }

    public function testCanSendEmailWithAttachments(): void
    {
        if (! $this->isMailServerAvailable()) {
            $this->markTestSkipped('Mail server not available for integration testing');
        }

        // Create a temporary test file
        $testFile = tempnam(sys_get_temp_dir(), 'email_test');
        file_put_contents($testFile, 'This is a test attachment content.');

        $message = new EmailMessage(
            'recipient@example.com',
            'Attachment Test',
            'This email has an attachment.'
        );
        $message->addAttachment($testFile, 'test-attachment.txt');

        $result = $this->emailService->send($message);

        $this->assertTrue($result->isSuccess());

        // Clean up
        unlink($testFile);
    }

    public function testCanSendFromPostData(): void
    {
        if (! $this->isMailServerAvailable()) {
            $this->markTestSkipped('Mail server not available for integration testing');
        }

        $postData = [
            'to' => 'recipient@example.com',
            'to_name' => 'Test Recipient',
            'subject' => 'POST Data Test',
            'body' => 'This email was sent using POST data format.',
            'html' => false,
            'cc' => ['cc@example.com'],
            'reply_to' => 'noreply@example.com',
        ];

        $result = $this->emailService->sendFromPost($postData);

        $this->assertTrue($result->isSuccess());
        $this->assertNotEmpty($result->getMessageId());
    }

    public function testHandlesInvalidSmtpConfiguration(): void
    {
        $invalidConfig = new EmailConfig([
            'smtp' => [
                'host' => 'invalid.smtp.server',
                'port' => 587,
                'username' => 'invalid@example.com',
                'password' => 'wrongpassword',
                'encryption' => 'tls',
                'auth' => true,
            ],
            'from' => [
                'address' => 'test@example.com',
                'name' => 'Test Service',
            ],
            'logging' => [
                'enabled' => false,
                'path' => '/tmp/test-email.log',
            ],
        ]);

        $emailService = new EmailService($invalidConfig);
        $message = new EmailMessage(
            'recipient@example.com',
            'Test Subject',
            'Test Body'
        );

        $result = $emailService->send($message);

        // Should return failure result instead of throwing exception
        $this->assertFalse($result->isSuccess());
        $this->assertNotEmpty($result->getError());
    }

    public function testValidateConfiguration(): void
    {
        if (! $this->isMailServerAvailable()) {
            $this->markTestSkipped('Mail server not available for integration testing');
        }

        $isValid = $this->emailService->validateConfiguration();
        $this->assertTrue($isValid);
    }

    public function testGetHealthStatus(): void
    {
        $health = $this->emailService->getHealthStatus();

        $this->assertArrayHasKey('status', $health);
        $this->assertArrayHasKey('smtp_connection', $health);
        $this->assertArrayHasKey('timestamp', $health);
        $this->assertArrayHasKey('config', $health);

        $this->assertContains($health['status'], ['healthy', 'unhealthy']);
        $this->assertIsBool($health['smtp_connection']);
    }

    private function isMailServerAvailable(): bool
    {
        // Check if we can connect to the test mail server
        $connection = @fsockopen(
            $this->config->get('smtp.host'),
            $this->config->get('smtp.port'),
            $errno,
            $errstr,
            5
        );

        if ($connection) {
            fclose($connection);

            return true;
        }

        return false;
    }
}

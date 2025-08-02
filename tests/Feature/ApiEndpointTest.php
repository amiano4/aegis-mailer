<?php

declare(strict_types=1);

namespace PHPMailService\Tests\Feature;

use PHPUnit\Framework\TestCase;

class ApiEndpointTest extends TestCase
{
    private string $baseUrl = 'http://localhost:8000';

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

        $response = $this->makeRequest('GET', '/health');

        $this->assertNotNull($response);
        $data = json_decode($response, true);

        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('smtp_connection', $data);
        $this->assertContains($data['status'], ['healthy', 'unhealthy']);
    }

    public function testStatusEndpointReturnsServiceInfo(): void
    {
        $this->markTestSkipped('Requires running server for feature tests');

        $response = $this->makeRequest('GET', '/status');

        $this->assertNotNull($response);
        $data = json_decode($response, true);

        $this->assertArrayHasKey('service', $data);
        $this->assertArrayHasKey('version', $data);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('endpoints', $data);
        $this->assertEquals('operational', $data['status']);
    }

    public function testSendEmailEndpointAcceptsValidRequest(): void
    {
        $this->markTestSkipped('Requires running server and mail server for feature tests');

        $emailData = [
            'to' => 'test@example.com',
            'subject' => 'Feature Test Email',
            'body' => 'This is a test email from the feature test suite.',
            'html' => false,
        ];

        $response = $this->makeRequest('POST', '/', $emailData);

        $this->assertNotNull($response);
        $data = json_decode($response, true);

        $this->assertTrue($data['success']);
        $this->assertEquals('Email sent successfully', $data['message']);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('message_id', $data['data']);
    }

    public function testSendEmailEndpointRejectsInvalidRequest(): void
    {
        $this->markTestSkipped('Requires running server for feature tests');

        $invalidEmailData = [
            'to' => 'invalid-email',
            'subject' => '',
            'body' => '',
        ];

        $response = $this->makeRequest('POST', '/', $invalidEmailData);

        $this->assertNotNull($response);
        $data = json_decode($response, true);

        $this->assertArrayHasKey('error', $data);
        $this->assertFalse($data['success'] ?? true);
    }

    public function testSendEmailEndpointRejectsGetRequest(): void
    {
        $this->markTestSkipped('Requires running server for feature tests');

        $response = $this->makeRequest('GET', '/');

        $this->assertNotNull($response);
        $data = json_decode($response, true);

        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('Method not allowed', $data['error']);
    }

    public function testApiKeyAuthenticationWhenRequired(): void
    {
        $this->markTestSkipped('Requires running server with API key configuration');

        // This would test API key authentication when enabled
        $emailData = [
            'to' => 'test@example.com',
            'subject' => 'Auth Test',
            'body' => 'Test body',
        ];

        // Without API key
        $response = $this->makeRequest('POST', '/', $emailData);
        $data = json_decode($response, true);
        $this->assertArrayHasKey('error', $data);

        // With valid API key
        $response = $this->makeRequest('POST', '/', $emailData, ['X-API-Key: valid-key']);
        $data = json_decode($response, true);
        $this->assertTrue($data['success']);
    }

    public function testCorsHeadersArePresent(): void
    {
        $this->markTestSkipped('Requires running server for feature tests');

        $headers = $this->getResponseHeaders('OPTIONS', '/');

        $this->assertArrayHasKey('Access-Control-Allow-Origin', $headers);
        $this->assertArrayHasKey('Access-Control-Allow-Methods', $headers);
        $this->assertArrayHasKey('Access-Control-Allow-Headers', $headers);
    }

    private function makeRequest(string $method, string $endpoint, array $data = [], array $headers = []): ?string
    {
        $url = $this->baseUrl . $endpoint;

        $context = [
            'http' => [
                'method' => $method,
                'header' => array_merge([
                    'Content-Type: application/json',
                    'User-Agent: PHPUnit Feature Test',
                ], $headers),
                'content' => $method === 'POST' ? json_encode($data) : null,
                'ignore_errors' => true,
            ],
        ];

        return @file_get_contents($url, false, stream_context_create($context));
    }

    private function getResponseHeaders(string $method, string $endpoint): array
    {
        $url = $this->baseUrl . $endpoint;

        $context = [
            'http' => [
                'method' => $method,
                'ignore_errors' => true,
            ],
        ];

        @file_get_contents($url, false, stream_context_create($context));

        $headers = [];
        if (isset($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (strpos($header, ':') !== false) {
                    [$key, $value] = explode(':', $header, 2);
                    $headers[trim($key)] = trim($value);
                }
            }
        }

        return $headers;
    }
}

# Aegis Mailer

**A Secure, Asynchronous, and Reliable Email Microservice for Modern Applications.**

[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

---

Aegis Mailer is an enterprise-grade email microservice built on modern PHP principles. It provides a simple, secure HTTP API for sending emails asynchronously, designed for high performance and reliability.

## Guiding Principles

- **Secure by Default:** Features like XSS protection, path traversal prevention, and constant-time token comparison are built-in, not optional.
- **Asynchronous-First:** API requests are queued immediately, providing a fast and resilient experience for the client. A background worker handles the time-consuming task of communicating with SMTP servers.
- **Domain-Driven Design:** A clean, layered architecture separates the core business logic from the framework and infrastructure, making the system highly maintainable and testable.
- **Dependency Injection:** All services are managed by a DI container, eliminating global state and making dependencies explicit.

## Features

- **Asynchronous Email Sending:** `202 Accepted` response for immediate API feedback.
- **Message Queue:** Powered by `enqueue`, supporting various backends (filesystem, Redis, etc.).
- **Secure API:** API key authentication and rate limiting.
- **XSS Protection:** Automatic HTML sanitization with `HTMLPurifier`.
- **Secure Attachments:** Safely handles in-memory attachments without exposing the filesystem.
- **Structured Logging:** PSR-3 compliant logging with `Monolog`.
- **Containerized:** Production-ready `Dockerfile` and `docker-compose.yml` for easy deployment.

## API Reference

### `POST /send`

Queues an email for sending.

**Headers**

- `Content-Type: application/json`
- `X-Api-Key: your-secret-api-key`

**Body**

```json
{
  "to": "recipient@example.com",
  "subject": "Hello from Aegis Mailer",
  "body": "<h1>Hello!</h1><p>This is a test email.</p>",
  "isHtml": true,
  "toName": "Test Recipient",
  "cc": [{ "email": "cc@example.com", "name": "CC Recipient" }],
  "bcc": [{ "email": "bcc@example.com" }],
  "replyTo": "support@example.com",
  "attachments": [
    {
      "name": "invoice.pdf",
      "content": "(base64 encoded content)"
    }
  ]
}
```

**Success Response (202 Accepted)**

```json
{
  "success": true,
  "message": "Email queued successfully",
  "message_id": "(a unique message ID)"
}
```

## Getting Started with Docker

1.  **Clone the repository:**

    ```bash
    git clone https://github.com/amiano4/aegis-mailer.git aegis-mailer
    cd aegis-mailer
    ```

2.  **Create your environment file:**

    ```bash
    cp .env.example .env
    ```

    _Edit `.env` and set your `API_KEY` and SMTP credentials._

3.  **Build and run the containers:**

    ```bash
    docker-compose up --build -d
    ```

4.  **The service is now available:**

    - **API:** `http://localhost:8080`
    - **Queue (Redis):** `localhost:6379`

5.  **Check the logs:**
    ```bash
    docker-compose logs -f app
    docker-compose logs -f worker
    ```

## Development

### Running Tests

(Test suite to be implemented)

### Local Worker

To run the queue worker locally without Docker:

```bash
php bin/worker
```

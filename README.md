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

### Queue Management

Aegis Mailer includes a comprehensive queue management utility for monitoring and controlling the email processing system:

```bash
# Show queue status and worker information
php bin/queue status

# Clear all pending messages from queue
php bin/queue clear

# Process all queued messages once (no continuous worker)
php bin/queue process

# Start worker in foreground mode
php bin/queue start

# Start worker as background daemon
php bin/queue start --daemon

# Stop running worker process
php bin/queue stop

# Restart worker (foreground mode)
php bin/queue restart

# Restart worker as background daemon
php bin/queue restart --daemon

# View recent application logs (default: 50 lines)
php bin/queue logs

# View specific number of log lines
php bin/queue logs 100

# Show help and available commands
php bin/queue help
```

#### Queue Status Example

```bash
$ php bin/queue status

=== Queue Status ===

Queue Directory: /var/queue
Queue Files: 3
Lock Files: 1
Total Size: 2.1 KB
⚠ There are 3 pending messages in the queue
✓ Worker is running (PID: 12345)
```

#### Production Daemon Mode

For production environments, use daemon mode with proper logging:

```bash
# Start worker as daemon
php bin/queue start --daemon

# Check logs
php bin/queue logs 50

# Monitor in real-time
tail -f var/logs/worker.log
```

### Testing the API

Test the email API with a simple curl command:

```bash
# Start the development server
php -S localhost:8080 -t public/

# Test email sending (replace YOUR_API_KEY with your actual key)
curl -X POST http://localhost:8080/send \
  -H "Content-Type: application/json" \
  -H "X-Api-Key: YOUR_API_KEY" \
  -d '{
    "to": "test@example.com",
    "subject": "Test Email",
    "body": "<h1>Hello!</h1><p>This is a test email from Aegis Mailer.</p>",
    "isHtml": true
  }'

# Expected response:
# {"success":true,"message":"Email queued successfully","message_id":"..."}
```

### Local Development

#### Manual Worker

To run the queue worker manually without the management utility:

```bash
php bin/worker
```

#### Development Server

Start the development server:

```bash
# Install dependencies
composer install

# Start PHP development server
php -S localhost:8080 -t public/

# In another terminal, start the worker
php bin/queue start
```

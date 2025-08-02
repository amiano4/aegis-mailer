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

**📖 [Complete API Documentation](./API_DOCUMENTATION.md)** - Comprehensive integration guide with examples, use cases, and production deployment.

### Quick API Overview

#### Core Endpoints

| Method | Endpoint | Auth Required | Description |
|--------|----------|---------------|-------------|
| `POST` | `/send` | ✅ | Queue email for delivery |
| `GET` | `/send/{id}/status` | ❌ | Check delivery status |
| `GET` | `/health` | ❌ | Health check for monitoring |
| `GET` | `/status` | ❌ | System status and metrics |

#### Basic Usage

**Send Email**
```bash
curl -X POST http://localhost:8000/send \
  -H "Content-Type: application/json" \
  -H "X-Api-Key: your-api-key" \
  -d '{
    "to": "user@example.com",
    "subject": "Hello",
    "body": "<h1>Welcome!</h1>",
    "isHtml": true
  }'
```

**Check Status**
```bash
curl "http://localhost:8000/send/msg_abc123/status?date=2024-08-02"
```

#### Webhook Notifications

Configure optional real-time delivery notifications:

```env
WEBHOOK_URL=https://your-app.com/api/webhooks
WEBHOOK_SECRET=shared-secret-key
```

**Webhook payload:**
```json
{
  "message_id": "msg_abc123",
  "status": "sent|failed", 
  "timestamp": "2024-08-02T19:30:00+00:00"
}
```

**Security:** All webhooks include `X-Aegis-Signature` header with SHA-256 HMAC for verification.

---

**For detailed integration examples, client libraries, production deployment, and advanced use cases, see the [Complete API Documentation](./API_DOCUMENTATION.md).**

## Getting Started

### Quick Local Setup

1.  **Clone the repository:**

    ```bash
    git clone https://github.com/amiano4/aegis-mailer.git aegis-mailer
    cd aegis-mailer
    ```

2.  **Install dependencies:**

    ```bash
    composer install
    ```

3.  **Create your environment file:**

    ```bash
    cp .env.example .env
    ```

    _Edit `.env` and set your `API_KEY` and SMTP credentials._

4.  **Start the development server:**

    ```bash
    composer start
    ```

5.  **Start the worker (in another terminal):**

    ```bash
    composer queue start --daemon
    ```

6.  **The service is now available:**

    - **API:** Check console output for auto-detected port (usually `http://localhost:8000`)
    - **Queue Management:** `composer queue status`

### Docker Setup

1.  **Follow steps 1-3 above, then:**

2.  **Build and run the containers:**

    ```bash
    docker-compose up --build -d
    ```

3.  **The service is now available:**

    - **API:** `http://localhost:8080`
    - **Queue (Redis):** `localhost:6379`

4.  **Check the logs:**
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

Test the email API and monitoring endpoints:

```bash
# Start the development server
composer start

# Test health check (no auth required)
curl http://localhost:8000/health

# Test status endpoint (no auth required)
curl http://localhost:8000/status

# Test message status (replace message ID and date)
curl "http://localhost:8000/send/msg_abc123/status?date=2024-08-02"

# Test email sending (replace YOUR_API_KEY with your actual key)
curl -X POST http://localhost:8000/send \
  -H "Content-Type: application/json" \
  -H "X-Api-Key: YOUR_API_KEY" \
  -d '{
    "to": "test@example.com",
    "subject": "Test Email",
    "body": "<h1>Hello!</h1><p>This is a test email from Aegis Mailer.</p>",
    "isHtml": true
  }'

# Expected responses:
# Health: {"status":"healthy",...}
# Status: {"service":"Aegis Mailer",...}
# Message Status: {"status":"sent","message":"Email delivered successfully",...}
# Send: {"success":true,"message":"Email queued successfully","message_id":"..."}
```

### Local Development

#### Quick Start

```bash
# Install dependencies
composer install

# Start development server (auto-detects available port from 8000+)
composer start

# In another terminal, start the worker
composer queue start
```

#### Composer Scripts

Aegis Mailer includes convenient composer scripts for development:

```bash
# Start development server with smart port detection
composer start

# Start server on specific port
composer start -- --port=8080

# Queue management utility
composer queue status
composer queue start --daemon
composer queue stop
composer queue clear

# Start worker process
composer worker
```

#### Manual Development Server

If you prefer manual control:

```bash
# Start PHP development server on specific port
php -S localhost:8080 -t public/

# Or use the smart start script directly
php scripts/start-server.php --port=8080
```

#### Manual Worker

To run the queue worker manually without the management utility:

```bash
php bin/worker
```

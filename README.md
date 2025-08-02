# PHPMail Server

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
[![Tests](https://img.shields.io/badge/Tests-44%20Passing-brightgreen.svg)](./tests)

**PHPMail Server** - A production-ready, pure PHP email server that accepts POST requests for reliable email delivery. Built with modern PHP practices, comprehensive testing, centralized configuration, and enterprise security features.

_Powered by [PHPMailer](https://github.com/PHPMailer/PHPMailer) - the world's most popular PHP email library._

## 🎯 **Core Purpose**

**PHPMail Server is an HTTP email server that accepts POST requests and sends emails via SMTP.**

### **What it does:**

1. **Receives** HTTP POST requests with email data (to, subject, body, etc.)
2. **Processes** and validates the email parameters
3. **Sends** emails through your configured SMTP provider (Gmail, Hostinger, etc.)
4. **Returns** JSON response with success/failure status

### **Simple Flow:**

```
HTTP POST Request → Email Server → SMTP Provider → Recipient's Inbox
```

**Perfect for:** Microservices, webhooks, contact forms, notifications, and any application that needs reliable email delivery without handling SMTP complexity directly.

## ✨ **Features**

### **Core Email Features**

- ✅ **POST Request Handling** - Primary purpose: accept and process email via HTTP POST
- ✅ **Multiple SMTP Providers** - Works with Gmail, Outlook, Hostinger, and custom SMTP servers
- ✅ **HTML & Text Emails** - Rich HTML content with plain text fallbacks
- ✅ **CC/BCC Support** - Multiple recipient handling
- ✅ **File Attachments** - Support for email attachments
- ✅ **Custom Headers** - Add custom email headers
- ✅ **Priority Levels** - High, Normal, Low priority emails
- ✅ **Reply-To Support** - Configure reply-to addresses

### **Enterprise Security**

- 🔒 **Centralized Configuration** - Secure environment variable handling with validation
- 🔒 **Input Validation** - Comprehensive validation of all email parameters
- 🔒 **API Key Authentication** - Optional API key protection
- 🔒 **Rate Limiting** - Protection against abuse and spam
- 🔒 **Domain Restrictions** - Limit sending to specific domains
- 🔒 **XSS Protection** - Safe handling of HTML content

### **Deliverability & Quality**

- 📧 **Spam Risk Analysis** - Built-in content analysis to avoid spam filters
- 📧 **Professional Templates** - Pre-built professional email templates
- 📧 **Deliverability Headers** - Proper email headers for better delivery
- 📧 **Content Suggestions** - Automatic suggestions for better email content

### **Monitoring & Logging**

- 📊 **Structured Logging** - Comprehensive logging with Monolog
- 📊 **Health Monitoring** - Built-in health check endpoints (`/health`, `/status`)
- 📊 **Error Tracking** - Detailed error reporting and recovery
- 📊 **Performance Metrics** - Built-in performance monitoring

### **Developer Experience**

- 🛠️ **Professional Testing** - 44+ unit tests, integration tests, feature tests
- 🛠️ **CI/CD Ready** - GitHub Actions workflow included
- 🛠️ **Development Tools** - Makefile with 20+ commands
- 🛠️ **Code Quality** - PHPStan, PHP-CS-Fixer integration
- 🛠️ **Documentation** - Comprehensive docs and examples

## 🚀 **Quick Start**

### **Installation**

```bash
# Clone the repository
git clone https://github.com/amiano4/phpmail-server.git
cd phpmail-service

# Install dependencies
composer install --no-dev

# Configure environment
cp .env.example .env
# Edit .env with your SMTP settings

# Start the server
composer start
```

### **Basic Usage**

Send a POST request to `http://localhost:8000`:

```bash
curl -X POST http://localhost:8000 \
  -H "Content-Type: application/json" \
  -d '{
    "to": "recipient@example.com",
    "subject": "Hello World",
    "body": "This is a test email",
    "html": false
  }'
```

**Response:**

```json
{
  "success": true,
  "message": "Email sent successfully",
  "data": {
    "message_id": "<unique-id@server>",
    "email_id": "uuid",
    "sent_at": "2025-08-02 12:34:56"
  }
}
```

## 📧 **Email API Reference**

### **Required Fields**

| Field     | Type   | Description             |
| --------- | ------ | ----------------------- |
| `to`      | string | Recipient email address |
| `subject` | string | Email subject line      |
| `body`    | string | Email content           |

### **Optional Fields**

| Field         | Type         | Description                                      |
| ------------- | ------------ | ------------------------------------------------ |
| `to_name`     | string       | Recipient display name                           |
| `html`        | boolean      | Set to `true` for HTML emails (default: `false`) |
| `text`        | string       | Plain text version for HTML emails               |
| `cc`          | string/array | CC recipients                                    |
| `bcc`         | string/array | BCC recipients                                   |
| `reply_to`    | string       | Reply-to email address                           |
| `attachments` | array        | File attachments (if enabled)                    |

### **Examples**

#### **Simple Text Email**

```json
{
  "to": "user@example.com",
  "subject": "Welcome!",
  "body": "Welcome to our service!"
}
```

#### **Rich HTML Email**

```json
{
  "to": "customer@example.com",
  "to_name": "Valued Customer",
  "subject": "Welcome to Our Service",
  "body": "<h1>Welcome!</h1><p>Thank you for joining us.</p>",
  "html": true,
  "text": "Welcome! Thank you for joining us.",
  "cc": ["team@company.com"],
  "reply_to": "support@company.com"
}
```

#### **Business Email with Multiple Recipients**

```json
{
  "to": "primary@example.com",
  "to_name": "Primary Recipient",
  "subject": "Quarterly Business Update",
  "body": "<h2>Q1 Update</h2><p>Here are the highlights...</p>",
  "html": true,
  "cc": ["manager@company.com", "team@company.com"],
  "bcc": ["analytics@company.com"],
  "reply_to": "business@company.com"
}
```

## 🔧 **Configuration**

### **Environment Variables**

Create `.env` file from `.env.example`:

```bash

# Application Settings
APP_NAME="Your Email Service"
APP_VERSION="1.0.0"
APP_ENV=production
APP_DEBUG=false

# SMTP Configuration (Required)
SMTP_HOST=smtp.your-provider.com
SMTP_PORT=587
SMTP_USERNAME=your-email@domain.com
SMTP_PASSWORD=your-password
SMTP_ENCRYPTION=tls
SMTP_TIMEOUT=30

# Sender Information (Required)
MAIL_FROM_ADDRESS=noreply@domain.com
MAIL_FROM_NAME="Your Service Name"

# Security Settings
API_KEY_REQUIRED=false
API_KEY=your-secret-api-key
RATE_LIMITING_ENABLED=true
RATE_LIMITING_MAX_ATTEMPTS=100
RATE_LIMITING_TIME_WINDOW=3600

# Features
TEMPLATES_ENABLED=true
ATTACHMENTS_ENABLED=true
LOGGING_ENABLED=true
LOG_LEVEL=info
```

### **SMTP Provider Examples**

#### **Gmail**

```bash
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_ENCRYPTION=tls
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password  # Use App Password, not regular password
```

#### **Hostinger**

```bash
SMTP_HOST=smtp.hostinger.com
SMTP_PORT=465
SMTP_ENCRYPTION=ssl
SMTP_USERNAME=your-email@your-domain.com
SMTP_PASSWORD=your-email-password
```

#### **Outlook/Hotmail**

```bash
SMTP_HOST=smtp-mail.outlook.com
SMTP_PORT=587
SMTP_ENCRYPTION=tls
SMTP_USERNAME=your-email@outlook.com
SMTP_PASSWORD=your-password
```

## 🧪 **Testing**

### **Local Testing Commands**

#### **Using Makefile (Recommended)**

```bash
# Quick unit tests (44 tests, ~0.02s)
make test-unit

# All tests
make test

# Generate coverage report
make test-coverage

# Run quality checks
make quality
```

#### **Using Composer**

```bash
# Run all tests
composer test

# Run unit tests only
composer test -- --testsuite="Unit Tests"

# Generate coverage
composer test-coverage
```

#### **Using PHPUnit Directly**

```bash
# All tests
vendor/bin/phpunit --no-coverage

# Unit tests only
vendor/bin/phpunit --testsuite="Unit Tests" --no-coverage

# Specific test
vendor/bin/phpunit tests/Unit/Config/EmailConfigTest.php
```

### **Test Types**

| Test Suite            | Count | Purpose                | Speed  |
| --------------------- | ----- | ---------------------- | ------ |
| **Unit Tests**        | 44    | Individual components  | ~0.02s |
| **Integration Tests** | 7     | Component interactions | ~2s    |
| **Feature Tests**     | 7     | Complete API workflows | ~5s    |

### **Testing with Real Email**

#### **Start Service**

```bash
# Start in background
make serve-bg

# Or start in foreground to see logs
composer start
```

#### **Send Test Emails**

```bash
# Quick test
make test-email

# Custom test
curl -X POST http://localhost:8000 \
  -H "Content-Type: application/json" \
  -d '{
    "to": "your-email@gmail.com",
    "subject": "Live Test Email",
    "body": "Testing actual email delivery!",
    "html": false
  }'
```

#### **Professional Email Test**

```bash
curl -X POST http://localhost:8000 \
  -H "Content-Type: application/json" \
  -d '{
    "to": "recipient@example.com",
    "to_name": "Test Recipient",
    "subject": "Professional Service Notification",
    "body": "<h1>Service Update</h1><p>This is a professional email with:</p><ul><li>Proper HTML formatting</li><li>Business-appropriate content</li><li>Professional structure</li></ul>",
    "html": true,
    "text": "Service Update: This is a professional email with proper formatting, business-appropriate content, and professional structure.",
    "reply_to": "support@your-domain.com"
  }'
```

### **Development Environment**

```bash
# Complete development setup
make dev
# Starts: Email service (localhost:8000) + MailHog (localhost:8025)

# Run all tests
make test

# Stop development environment
make dev-stop
```

## 📊 **API Endpoints**

| Method        | Endpoint     | Description                     | Response           |
| ------------- | ------------ | ------------------------------- | ------------------ |
| `POST /`      | Send email   | Primary email sending endpoint  | Success/Error JSON |
| `POST /send`  | Send email   | Alternative email endpoint      | Success/Error JSON |
| `GET /health` | Health check | Service health status           | Health status JSON |
| `GET /status` | Service info | Service information and version | Service info JSON  |

### **Health Check**

```bash
curl http://localhost:8000/health
```

```json
{
  "status": "healthy",
  "smtp_connection": true,
  "timestamp": "2025-08-02 12:34:56",
  "config": {
    "host": "smtp.hostinger.com",
    "port": 465,
    "encryption": "ssl"
  }
}
```

### **Service Status**

```bash
curl http://localhost:8000/status
```

```json
{
  "service": "PHPMail Server",
  "version": "1.0.0",
  "status": "operational",
  "timestamp": "2025-08-02 12:34:56",
  "environment": "production",
  "endpoints": {
    "POST /": "Send email",
    "POST /send": "Send email",
    "GET /health": "Health check",
    "GET /status": "Service status"
  }
}
```

## 🔐 **Security Features**

### **API Key Authentication**

```bash
# Enable in .env
API_KEY_REQUIRED=true
API_KEY=your-secret-key

# Use in requests
curl -X POST http://localhost:8000 \
  -H "X-API-Key: your-secret-key" \
  -H "Content-Type: application/json" \
  -d '{"to":"test@example.com","subject":"Test","body":"Hello"}'
```

### **Rate Limiting**

- Configurable request limits per time window
- Protection against abuse and spam
- Automatic blocking of excessive requests

### **Input Validation**

- Email address validation with filter_var
- Required field checking
- Attachment size limits
- XSS protection for HTML content
- Type casting and bounds checking

### **Centralized Security**

All environment variables are validated through a centralized security layer:

- **Email validation** - Ensures valid email formats
- **Port validation** - Restricts to valid port ranges (1-65535)
- **Choice validation** - Only allows predefined values
- **Path validation** - Safely handles file paths
- **Array validation** - Properly parses comma-separated values

## 📝 **Logging & Monitoring**

### **Structured Logging**

```bash
# Log location
logs/email-YYYY-MM-DD.log

# Log levels: debug, info, warning, error, critical
LOG_LEVEL=info
```

### **Log Entries Include**

- Email sending success/failure
- Rate limiting events
- Security events (invalid API keys, etc.)
- System errors and exceptions
- Performance metrics

### **View Logs**

```bash
# Recent logs
make logs

# Follow logs in real-time
tail -f logs/email-$(date +%Y-%m-%d).log
```

### **Monitoring Commands**

```bash
# Check service health
make health

# Check service status
make status

# View logs
make logs
```

## 🛠 **Development Tools**

### **Available Commands**

```bash
make help                    # Show all available commands

# Testing
make test                    # Run all tests
make test-unit              # Run unit tests (fastest)
make test-integration       # Run integration tests
make test-coverage          # Generate coverage report

# Quality
make cs                     # Check code style
make cs-fix                 # Fix code style issues
make stan                   # Run static analysis
make quality                # Run all quality checks

# Development
make serve                  # Start development server
make dev                    # Setup complete dev environment
make dev-stop               # Stop development environment

# Email Testing
make test-email             # Send test email
make mailhog-start          # Start MailHog for testing
make mailhog-stop           # Stop MailHog

# Utilities
make clean                  # Clean temporary files
make logs                   # Show recent logs
make health                 # Check service health
```

### **Development Workflow**

```bash
# 1. Setup development environment
make dev

# 2. Make your changes...

# 3. Run quick tests during development
make test-unit              # ~0.02 seconds

# 4. Before committing
make quality                # Style, analysis, and tests

# 5. Test with real emails
make test-email

# 6. Cleanup
make dev-stop
```

## 🚀 **Production Deployment**

### **Requirements**

- PHP 8.1+
- Composer
- SMTP server access
- Web server (Apache/Nginx)

### **Production Checklist**

- [ ] Set up environment variables
- [ ] Configure SMTP provider
- [ ] Enable logging
- [ ] Set up rate limiting
- [ ] Configure API key authentication
- [ ] Set up monitoring
- [ ] Test email delivery
- [ ] Configure web server
- [ ] Set up SSL certificate
- [ ] Configure firewall rules

### **Deployment Commands**

```bash
# Install production dependencies
composer install --no-dev --optimize-autoloader

# Run production tests
make ci

# Check configuration
make health
```

### **Docker Support**

```dockerfile
FROM php:8.1-apache
COPY . /var/www/html/
RUN composer install --no-dev --optimize-autoloader
EXPOSE 80
```

### **Web Server Configuration**

#### **Apache**

```apache
<VirtualHost *:80>
    DocumentRoot /path/to/phpmail-service/public
    <Directory /path/to/phpmail-service/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### **Nginx**

```nginx
server {
    listen 80;
    root /path/to/phpmail-service/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }
}
```

## 🔄 **CI/CD**

### **GitHub Actions**

Automated testing pipeline included in `.github/workflows/tests.yml`:

- ✅ **Multi-PHP Testing** (8.1, 8.2, 8.3)
- ✅ **Code Quality Checks** (PHPStan, PHP-CS-Fixer)
- ✅ **Security Audits**
- ✅ **Coverage Reports**
- ✅ **MailHog Integration** for email testing

### **Local CI Simulation**

```bash
# Simulate the complete CI pipeline
make ci
```

## 🤝 **Contributing**

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Run quality checks: `make quality`
6. Submit a pull request

### **Development Setup**

```bash
# Install all dependencies (including dev tools)
composer install

# Run quality checks
make quality

# Run all tests
make test
```

## 🙏 **Credits & Acknowledgments**

### **Core Dependencies**

- **[PHPMailer](https://github.com/PHPMailer/PHPMailer)** - The robust, feature-rich email library that powers PHPMail Server's email delivery
- **[Monolog](https://github.com/Seldaek/monolog)** - Structured logging for comprehensive monitoring
- **[PHPUnit](https://github.com/sebastianbergmann/phpunit)** - Professional testing framework
- **[phpdotenv](https://github.com/vlucas/phpdotenv)** - Environment variable management

### **Special Thanks**

- **Marcus Bointon** and **Jim Jagielski** - Lead maintainers of PHPMailer
- **The PHPMailer Contributors** - For creating and maintaining the most reliable PHP email library
- **The PHP Community** - For continuous innovation and excellent tooling

_PHPMail Server stands on the shoulders of giants. Thank you to all open-source contributors who make projects like this possible._

## 📄 **License**

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 **Support & Troubleshooting**

### **Common Issues**

#### **Emails not sending**

```bash
# Check SMTP configuration
make health

# Check logs
make logs

# Test with simple email
curl -X POST http://localhost:8000 \
  -H "Content-Type: application/json" \
  -d '{"to":"test@gmail.com","subject":"Test","body":"Simple test"}'
```

#### **Emails going to spam**

- Use professional subject lines (avoid "test", promotional words)
- Include proper business content
- Set up SPF/DKIM records for your domain
- Use the built-in spam risk analyzer

#### **Tests failing**

```bash
# Run specific test suite
make test-unit

# Check test output
vendor/bin/phpunit --testsuite="Unit Tests" --no-coverage --verbose
```

### **Getting Help**

- **Issues**: Report bugs via GitHub Issues
- **Documentation**: Check `/docs` for detailed guides
- **Examples**: See test files for implementation examples

## 📊 **Project Stats**

- **✅ 58 Total Tests** (44 Unit, 7 Integration, 7 Feature)
- **🔒 100% Environment Variable Validation**
- **📧 Professional Email Templates**
- **⚡ ~0.02s Unit Test Execution**
- **🛡️ Enterprise Security Features**
- **📱 REST API with JSON responses**
- **🔄 CI/CD Ready**

**Built for reliability, designed for simplicity.**

**Core Function: HTTP POST → Email Delivery → JSON Response**

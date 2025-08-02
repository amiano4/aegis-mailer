# Aegis Mailer v1.0.0 🛡️

**A Secure, Asynchronous, and Reliable Email Microservice for Modern Applications**

## 🚀 What's New

Aegis Mailer v1.0 introduces a production-ready email microservice built with modern PHP principles, featuring asynchronous processing, enterprise-grade security, and comprehensive queue management.

## ✨ Key Features

### 🔒 **Security First**
- **XSS Protection**: Automatic HTML sanitization with HTMLPurifier
- **API Key Authentication**: Secure endpoint access control
- **Rate Limiting**: Built-in protection against abuse
- **Secure Attachments**: In-memory processing without filesystem exposure

### ⚡ **Asynchronous Processing**
- **Instant API Response**: 202 Accepted for immediate feedback
- **Background Workers**: Reliable SMTP processing with daemon mode
- **Message Queuing**: Filesystem-based queue with Redis support
- **Fault Tolerance**: Automatic retry and error handling

### 🏗️ **Enterprise Architecture**
- **Domain-Driven Design**: Clean, maintainable codebase
- **CQRS Pattern**: Separated command/query responsibilities  
- **Dependency Injection**: Zero global state, explicit dependencies
- **PSR-3 Logging**: Structured logging with comprehensive metadata

### 🛠️ **Production-Ready Tools**
- **Queue Management CLI**: Complete control over email processing
- **Worker Daemon Mode**: Background processing with PID management
- **Force Stop**: SIGKILL option for stuck processes
- **Real-time Monitoring**: Queue status, logs, and metrics

## 📦 Installation

```bash
git clone https://github.com/your-org/aegis-mailer.git
cd aegis-mailer
composer install
cp .env.example .env
# Configure your SMTP settings and API key
php bin/queue start --daemon
```

## 🎯 API Endpoints

### `POST /send` - Queue Email for Delivery
```json
{
  "to": "user@example.com",
  "subject": "Welcome!",
  "body": "<h1>Hello World!</h1>",
  "isHtml": true,
  "attachments": [{"name": "file.pdf", "content": "base64..."}]
}
```

## 🔧 Queue Management

```bash
# Production daemon mode
php bin/queue start --daemon

# Monitor status
php bin/queue status

# Process pending messages
php bin/queue process

# View logs
php bin/queue logs
```

## 🌟 Perfect For

- **SaaS Applications**: Transactional emails at scale
- **E-commerce**: Order confirmations, shipping updates
- **Microservices**: Decoupled email service
- **Enterprise**: Reliable, auditable email delivery

## 🎨 Technical Highlights

- **PHP 8.1+** with modern features
- **Zero-config Docker** deployment
- **Unicode/UTF-8** support for global applications  
- **Comprehensive logging** for debugging and compliance
- **Horizontal scaling** ready

## 📊 What's Inside

- 🏛️ Domain-driven architecture with CQRS
- 📮 Asynchronous message processing
- 🔐 Enterprise security standards
- 📝 Comprehensive error handling and logging
- 🚀 Production daemon management
- 📚 Complete API documentation

---

**Ready to send emails the right way?** Get started with Aegis Mailer v1.0 today! 🎉
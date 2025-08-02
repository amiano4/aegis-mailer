# Aegis Mailer API Documentation

**Complete Integration Guide for Developers**

Version: 1.0.0  
Base URL: `http://your-domain.com` or `http://localhost:8000`

---

## Table of Contents

1. [Authentication](#authentication)
2. [Core Endpoints](#core-endpoints)
3. [Integration Patterns](#integration-patterns)
4. [Use Cases & Examples](#use-cases--examples)
5. [Error Handling](#error-handling)
6. [Rate Limiting](#rate-limiting)
7. [Webhook Integration](#webhook-integration)
8. [Client Libraries](#client-libraries)
9. [Testing & Debugging](#testing--debugging)
10. [Production Deployment](#production-deployment)

---

## Authentication

Aegis Mailer uses API key authentication for protected endpoints.

### API Key Setup

1. Configure your API key in `.env`:
```env
API_KEY=your-secret-api-key-here
```

2. Include the API key in request headers:
```
X-Api-Key: your-secret-api-key-here
```

### Public Endpoints (No Auth Required)
- `GET /health` - Health check
- `GET /status` - System status
- `GET /send/{messageId}/status` - Message delivery status

### Protected Endpoints (Auth Required)
- `POST /send` - Send email

---

## Core Endpoints

### 1. Send Email

**Endpoint:** `POST /send`  
**Authentication:** Required  
**Rate Limited:** Yes (100 requests/hour by default)

#### Request Headers
```
Content-Type: application/json
X-Api-Key: your-secret-api-key
```

#### Request Body Schema
```json
{
  "to": "string (required)",
  "subject": "string (required)", 
  "body": "string (required)",
  "isHtml": "boolean (optional, default: false)",
  "toName": "string (optional)",
  "cc": [
    {
      "email": "string (required)",
      "name": "string (optional)"
    }
  ],
  "bcc": [
    {
      "email": "string (required)", 
      "name": "string (optional)"
    }
  ],
  "replyTo": "string (optional)",
  "attachments": [
    {
      "name": "string (required)",
      "content": "string (required, base64 encoded)"
    }
  ],
  "priority": "integer (optional, 1-5, default: 3)",
  "headers": {
    "custom-header": "value"
  }
}
```

#### Response (202 Accepted)
```json
{
  "success": true,
  "message": "Email queued successfully",
  "message_id": "msg_abc123def456"
}
```

### 2. Check Message Status

**Endpoint:** `GET /send/{messageId}/status`  
**Authentication:** Not required  
**Rate Limited:** No

#### Parameters
- `messageId` (path): Message ID from send response
- `date` (query, optional): Date hint (YYYY-MM-DD) for faster lookup

#### Response - Success
```json
{
  "message_id": "msg_abc123def456",
  "lookup_date": "2024-08-02",
  "timestamp": "2024-08-02T19:30:00+00:00",
  "status": "sent",
  "message": "Email delivered successfully"
}
```

#### Response - Failed
```json
{
  "message_id": "msg_abc123def456",
  "lookup_date": "2024-08-02",
  "timestamp": "2024-08-02T19:30:00+00:00", 
  "status": "failed",
  "message": "Email delivery failed",
  "details": {
    "message_id": "msg_abc123def456",
    "failed_at": "2024-08-02T18:45:00+00:00",
    "attempts": 1,
    "last_error": "SMTP connection timeout"
  }
}
```

### 3. Health Check

**Endpoint:** `GET /health`  
**Authentication:** Not required  
**Rate Limited:** No

#### Response - Healthy (200)
```json
{
  "status": "healthy",
  "timestamp": "2024-08-02T19:30:00+00:00",
  "version": "1.0.0",
  "checks": {
    "queue": {"status": "healthy", "message": "Queue system operational"},
    "config": {"status": "healthy", "message": "Configuration loaded"},
    "smtp_config": {"status": "healthy", "message": "SMTP configuration present"},
    "logging": {"status": "healthy", "message": "Log directory writable"},
    "queue_storage": {"status": "healthy", "message": "Queue storage writable"}
  }
}
```

#### Response - Unhealthy (503)
Same structure with `"status": "unhealthy"` and failed check details.

### 4. System Status

**Endpoint:** `GET /status`  
**Authentication:** Not required  
**Rate Limited:** No

#### Response (200)
```json
{
  "service": "Aegis Mailer",
  "version": "1.0.0",
  "timestamp": "2024-08-02T19:30:00+00:00",
  "uptime": "2d 14h 23m",
  "queue": {
    "pending_messages": 0,
    "lock_files": 0, 
    "total_size_bytes": 0,
    "total_size_human": "0 B",
    "directory": "/var/queue"
  },
  "workers": {
    "count": 1,
    "pids": [12345],
    "status": "running"
  },
  "environment": {
    "php_version": "8.1.0",
    "memory_usage": 2097152,
    "memory_peak": 4194304,
    "memory_limit": "128M"
  }
}
```

---

## Integration Patterns

### 1. Basic Email Sending

**Simple Text Email**
```bash
curl -X POST http://localhost:8000/send \
  -H "Content-Type: application/json" \
  -H "X-Api-Key: your-api-key" \
  -d '{
    "to": "user@example.com",
    "subject": "Welcome!",
    "body": "Thank you for signing up."
  }'
```

**HTML Email with Styling**
```bash
curl -X POST http://localhost:8000/send \
  -H "Content-Type: application/json" \
  -H "X-Api-Key: your-api-key" \
  -d '{
    "to": "user@example.com",
    "subject": "Welcome to Our Platform!",
    "body": "<html><body><h1 style=\"color: blue;\">Welcome!</h1><p>Thank you for joining us.</p></body></html>",
    "isHtml": true
  }'
```

### 2. Email with Attachments

```bash
curl -X POST http://localhost:8000/send \
  -H "Content-Type: application/json" \
  -H "X-Api-Key: your-api-key" \
  -d '{
    "to": "customer@example.com",
    "subject": "Your Invoice",
    "body": "<h2>Invoice Attached</h2><p>Please find your invoice attached.</p>",
    "isHtml": true,
    "attachments": [
      {
        "name": "invoice-2024-001.pdf",
        "content": "JVBERi0xLjQKJc..."
      }
    ]
  }'
```

### 3. Bulk Email (CC/BCC)

```bash
curl -X POST http://localhost:8000/send \
  -H "Content-Type: application/json" \
  -H "X-Api-Key: your-api-key" \
  -d '{
    "to": "primary@example.com",
    "subject": "Team Update",
    "body": "Monthly team update...",
    "cc": [
      {"email": "manager@example.com", "name": "Manager"},
      {"email": "lead@example.com", "name": "Team Lead"}
    ],
    "bcc": [
      {"email": "archive@example.com"}
    ]
  }'
```

### 4. Delivery Status Tracking

```bash
# Send email and get message ID
RESPONSE=$(curl -X POST http://localhost:8000/send \
  -H "Content-Type: application/json" \
  -H "X-Api-Key: your-api-key" \
  -d '{"to":"test@example.com","subject":"Test","body":"Hello"}')

MESSAGE_ID=$(echo $RESPONSE | jq -r '.message_id')
DATE=$(date +%Y-%m-%d)

# Check status with date hint for faster lookup
curl "http://localhost:8000/send/$MESSAGE_ID/status?date=$DATE"
```

---

## Use Cases & Examples

### 1. E-commerce Order Confirmation

```javascript
// Node.js example
async function sendOrderConfirmation(order) {
  const emailData = {
    to: order.customerEmail,
    toName: order.customerName,
    subject: `Order Confirmation #${order.id}`,
    body: `
      <html>
        <body>
          <h2>Thank you for your order!</h2>
          <p>Order ID: ${order.id}</p>
          <p>Total: $${order.total}</p>
          <p>Expected delivery: ${order.deliveryDate}</p>
        </body>
      </html>
    `,
    isHtml: true,
    replyTo: 'support@yourstore.com'
  };

  const response = await fetch('http://your-aegis-mailer.com/send', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Api-Key': process.env.AEGIS_API_KEY
    },
    body: JSON.stringify(emailData)
  });

  const result = await response.json();
  
  // Store message ID for tracking
  await storeEmailTracking(order.id, result.message_id);
  
  return result;
}
```

### 2. User Registration Welcome Series

```php
<?php
class WelcomeEmailSeries {
    private $apiKey;
    private $baseUrl;
    
    public function __construct($apiKey, $baseUrl) {
        $this->apiKey = $apiKey;
        $this->baseUrl = $baseUrl;
    }
    
    public function sendWelcomeEmail($user) {
        $emailData = [
            'to' => $user['email'],
            'toName' => $user['name'],
            'subject' => 'Welcome to Our Platform!',
            'body' => $this->getWelcomeTemplate($user),
            'isHtml' => true,
            'priority' => 2 // High priority
        ];
        
        return $this->sendEmail($emailData);
    }
    
    public function sendEmail($data) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl . '/send',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Api-Key: ' . $this->apiKey
            ],
            CURLOPT_POSTFIELDS => json_encode($data)
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 202) {
            return json_decode($response, true);
        }
        
        throw new Exception("Email send failed: $response");
    }
    
    private function getWelcomeTemplate($user) {
        return "
            <html>
            <body style='font-family: Arial, sans-serif;'>
                <h1>Welcome {$user['name']}!</h1>
                <p>We're excited to have you on board.</p>
                <a href='https://yourapp.com/getting-started' 
                   style='background: #007cba; color: white; padding: 10px 20px; 
                          text-decoration: none; border-radius: 5px;'>
                    Get Started
                </a>
            </body>
            </html>
        ";
    }
}
```

### 3. Password Reset Flow

```python
import requests
import json
import secrets
from datetime import datetime, timedelta

class PasswordResetService:
    def __init__(self, api_key, base_url):
        self.api_key = api_key
        self.base_url = base_url
        
    def send_reset_email(self, user_email, reset_token):
        reset_url = f"https://yourapp.com/reset-password?token={reset_token}"
        
        email_data = {
            "to": user_email,
            "subject": "Password Reset Request",
            "body": f"""
                <html>
                <body>
                    <h2>Password Reset</h2>
                    <p>Click the link below to reset your password:</p>
                    <a href="{reset_url}" 
                       style="background: #dc3545; color: white; padding: 10px 20px; 
                              text-decoration: none; border-radius: 5px;">
                        Reset Password
                    </a>
                    <p><small>This link expires in 1 hour.</small></p>
                </body>
                </html>
            """,
            "isHtml": True,
            "priority": 1  # Highest priority for security emails
        }
        
        response = requests.post(
            f"{self.base_url}/send",
            headers={
                "Content-Type": "application/json",
                "X-Api-Key": self.api_key
            },
            json=email_data
        )
        
        if response.status_code == 202:
            result = response.json()
            self.track_reset_email(user_email, result['message_id'], reset_token)
            return result
        else:
            raise Exception(f"Email send failed: {response.text}")
    
    def track_reset_email(self, email, message_id, token):
        # Store for tracking and security audit
        pass
```

### 4. Newsletter System

```go
package main

import (
    "bytes"
    "encoding/json"
    "fmt"
    "net/http"
    "time"
)

type EmailService struct {
    APIKey  string
    BaseURL string
}

type NewsletterEmail struct {
    To          string `json:"to"`
    ToName      string `json:"toName"`
    Subject     string `json:"subject"`
    Body        string `json:"body"`
    IsHtml      bool   `json:"isHtml"`
    UnsubscribeURL string `json:"-"`
}

func (es *EmailService) SendNewsletter(subscribers []Subscriber, content string) error {
    for _, subscriber := range subscribers {
        email := NewsletterEmail{
            To:      subscriber.Email,
            ToName:  subscriber.Name,
            Subject: "Weekly Newsletter",
            Body:    es.buildNewsletterHTML(content, subscriber.UnsubscribeToken),
            IsHtml:  true,
        }
        
        messageID, err := es.sendEmail(email)
        if err != nil {
            fmt.Printf("Failed to send to %s: %v\n", subscriber.Email, err)
            continue
        }
        
        // Track for delivery status
        es.trackNewsletter(subscriber.ID, messageID)
        
        // Rate limiting - don't overwhelm the service
        time.Sleep(100 * time.Millisecond)
    }
    
    return nil
}

func (es *EmailService) sendEmail(email NewsletterEmail) (string, error) {
    data, _ := json.Marshal(email)
    
    req, _ := http.NewRequest("POST", es.BaseURL+"/send", bytes.NewBuffer(data))
    req.Header.Set("Content-Type", "application/json")
    req.Header.Set("X-Api-Key", es.APIKey)
    
    client := &http.Client{Timeout: 30 * time.Second}
    resp, err := client.Do(req)
    if err != nil {
        return "", err
    }
    defer resp.Body.Close()
    
    if resp.StatusCode != 202 {
        return "", fmt.Errorf("unexpected status: %d", resp.StatusCode)
    }
    
    var result map[string]interface{}
    json.NewDecoder(resp.Body).Decode(&result)
    
    return result["message_id"].(string), nil
}

func (es *EmailService) buildNewsletterHTML(content, unsubscribeToken string) string {
    unsubscribeURL := fmt.Sprintf("https://yourapp.com/unsubscribe?token=%s", unsubscribeToken)
    
    return fmt.Sprintf(`
        <html>
        <body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            %s
            <hr style="margin: 30px 0;">
            <p style="font-size: 12px; color: #666;">
                <a href="%s">Unsubscribe</a> from these emails.
            </p>
        </body>
        </html>
    `, content, unsubscribeURL)
}
```

---

## Error Handling

### HTTP Status Codes

| Code | Meaning | Description |
|------|---------|-------------|
| 202 | Accepted | Email queued successfully |
| 400 | Bad Request | Invalid request data |
| 401 | Unauthorized | Invalid or missing API key |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server error |
| 503 | Service Unavailable | Service unhealthy |

### Error Response Format

```json
{
  "error": "Error type",
  "message": "Detailed error description",
  "code": "ERROR_CODE",
  "timestamp": "2024-08-02T19:30:00+00:00"
}
```

### Common Errors

**Invalid API Key (401)**
```json
{
  "error": "Unauthorized",
  "message": "Invalid API key",
  "code": "INVALID_API_KEY"
}
```

**Rate Limit Exceeded (429)**
```json
{
  "error": "Too Many Requests",
  "message": "Rate limit exceeded. Try again later.",
  "code": "RATE_LIMIT_EXCEEDED",
  "retry_after": 3600
}
```

**Validation Error (400)**
```json
{
  "error": "Validation Failed",
  "message": "Invalid email address",
  "code": "INVALID_EMAIL",
  "field": "to"
}
```

### Error Handling Best Practices

```javascript
async function sendEmailWithRetry(emailData, maxRetries = 3) {
  for (let attempt = 1; attempt <= maxRetries; attempt++) {
    try {
      const response = await fetch('/send', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Api-Key': API_KEY
        },
        body: JSON.stringify(emailData)
      });
      
      if (response.status === 202) {
        return await response.json();
      }
      
      if (response.status === 429) {
        // Rate limited - exponential backoff
        const delay = Math.pow(2, attempt) * 1000;
        await new Promise(resolve => setTimeout(resolve, delay));
        continue;
      }
      
      if (response.status >= 400 && response.status < 500) {
        // Client error - don't retry
        throw new Error(`Client error: ${response.status}`);
      }
      
      // Server error - retry
      if (attempt === maxRetries) {
        throw new Error(`Max retries exceeded`);
      }
      
    } catch (error) {
      if (attempt === maxRetries) {
        throw error;
      }
      
      // Exponential backoff for network errors
      const delay = Math.pow(2, attempt) * 1000;
      await new Promise(resolve => setTimeout(resolve, delay));
    }
  }
}
```

---

## Rate Limiting

### Default Limits
- **100 requests per hour** per API key
- **Sliding window** implementation
- **Per-endpoint** rate limiting

### Rate Limit Headers
```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 85
X-RateLimit-Reset: 1627847400
```

### Handling Rate Limits

```python
import time
import requests

def send_email_batch(emails, api_key):
    for email in emails:
        response = requests.post('/send', 
                               headers={'X-Api-Key': api_key},
                               json=email)
        
        if response.status_code == 429:
            # Rate limited - check retry after
            retry_after = int(response.headers.get('Retry-After', 60))
            print(f"Rate limited. Waiting {retry_after} seconds...")
            time.sleep(retry_after)
            
            # Retry the same email
            response = requests.post('/send',
                                   headers={'X-Api-Key': api_key}, 
                                   json=email)
        
        if response.status_code == 202:
            result = response.json()
            print(f"Email sent: {result['message_id']}")
        else:
            print(f"Failed to send email: {response.text}")
```

---

## Webhook Integration

### Webhook Setup

1. **Configure webhook URL in `.env`:**
```env
WEBHOOK_URL=https://your-app.com/api/webhooks/email
WEBHOOK_SECRET=your-shared-secret
```

2. **Implement webhook endpoint in your application:**

```javascript
// Express.js webhook handler
app.post('/api/webhooks/email', express.raw({type: 'application/json'}), (req, res) => {
  const signature = req.headers['x-aegis-signature'];
  const payload = req.body;
  
  // Verify signature
  const expectedSignature = 'sha256=' + 
    crypto.createHmac('sha256', WEBHOOK_SECRET)
          .update(payload)
          .digest('hex');
  
  if (!crypto.timingSafeEqual(Buffer.from(signature), Buffer.from(expectedSignature))) {
    return res.status(401).send('Unauthorized');
  }
  
  // Process webhook
  const data = JSON.parse(payload);
  handleEmailStatus(data);
  
  res.status(200).send('OK');
});

function handleEmailStatus(data) {
  const { message_id, status, timestamp, error, attempts } = data;
  
  if (status === 'sent') {
    console.log(`Email ${message_id} delivered successfully`);
    updateEmailStatus(message_id, 'delivered');
  } else if (status === 'failed') {
    console.log(`Email ${message_id} failed: ${error}`);
    updateEmailStatus(message_id, 'failed', error, attempts);
    
    // Implement retry logic or alerting
    if (attempts >= 3) {
      alertAdministrators(`Email ${message_id} permanently failed`);
    }
  }
}
```

### Webhook Security

**Always verify webhook signatures:**

```php
<?php
function verifyWebhookSignature($payload, $signature, $secret) {
    $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
    return hash_equals($signature, $expectedSignature);
}

// Webhook handler
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_AEGIS_SIGNATURE'] ?? '';
$secret = $_ENV['WEBHOOK_SECRET'];

if (!verifyWebhookSignature($payload, $signature, $secret)) {
    http_response_code(401);
    exit('Unauthorized');
}

$data = json_decode($payload, true);
handleEmailWebhook($data);

function handleEmailWebhook($data) {
    // Update database
    $stmt = $pdo->prepare("UPDATE email_logs SET status = ?, updated_at = ? WHERE message_id = ?");
    $stmt->execute([$data['status'], $data['timestamp'], $data['message_id']]);
    
    // Send notifications if needed
    if ($data['status'] === 'failed') {
        notifyOnEmailFailure($data);
    }
}
?>
```

---

## Client Libraries

### Official SDKs (Coming Soon)
- **PHP SDK** - Composer package
- **JavaScript/Node.js SDK** - NPM package  
- **Python SDK** - PyPI package
- **Go SDK** - Go module

### Community Libraries
- Submit your integration for inclusion

### DIY Integration Examples

**PHP cURL Wrapper**
```php
<?php
class AegisMailerClient {
    private $apiKey;
    private $baseUrl;
    
    public function __construct($apiKey, $baseUrl = 'http://localhost:8000') {
        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
    }
    
    public function send($emailData) {
        return $this->makeRequest('POST', '/send', $emailData);
    }
    
    public function getStatus($messageId, $date = null) {
        $endpoint = "/send/{$messageId}/status";
        if ($date) {
            $endpoint .= "?date={$date}";
        }
        return $this->makeRequest('GET', $endpoint);
    }
    
    public function health() {
        return $this->makeRequest('GET', '/health');
    }
    
    private function makeRequest($method, $endpoint, $data = null) {
        $ch = curl_init();
        $url = $this->baseUrl . $endpoint;
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Api-Key: ' . $this->apiKey
            ]
        ]);
        
        if ($method === 'POST' && $data) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $decoded = json_decode($response, true);
        
        return [
            'status_code' => $httpCode,
            'data' => $decoded,
            'success' => $httpCode >= 200 && $httpCode < 300
        ];
    }
}

// Usage
$mailer = new AegisMailerClient('your-api-key');

$result = $mailer->send([
    'to' => 'user@example.com',
    'subject' => 'Test Email',
    'body' => 'Hello from Aegis Mailer!',
    'isHtml' => false
]);

if ($result['success']) {
    $messageId = $result['data']['message_id'];
    echo "Email sent with ID: {$messageId}";
} else {
    echo "Error: " . $result['data']['error'];
}
?>
```

---

## Testing & Debugging

### Development Environment

**Start Aegis Mailer in development mode:**
```bash
# Clone and setup
git clone https://github.com/amiano4/aegis-mailer.git
cd aegis-mailer
composer install
cp .env.example .env

# Configure for testing
echo "API_KEY=test-api-key" >> .env
echo "SMTP_HOST=localhost" >> .env
echo "SMTP_PORT=1025" >> .env

# Start services
composer start          # Development server
composer queue start    # Email worker
```

**Use MailHog for email testing:**
```bash
# Install MailHog (macOS)
brew install mailhog

# Start MailHog
mailhog

# Configure Aegis Mailer to use MailHog
echo "SMTP_HOST=localhost" >> .env
echo "SMTP_PORT=1025" >> .env
echo "SMTP_AUTH=false" >> .env
```

### Testing Scripts

**Basic API Test**
```bash
#!/bin/bash
API_KEY="test-api-key"
BASE_URL="http://localhost:8000"

echo "Testing Aegis Mailer API..."

# Health check
echo "1. Health Check:"
curl -s "$BASE_URL/health" | jq .

# Send test email
echo "2. Sending test email:"
RESPONSE=$(curl -s -X POST "$BASE_URL/send" \
  -H "Content-Type: application/json" \
  -H "X-Api-Key: $API_KEY" \
  -d '{
    "to": "test@example.com",
    "subject": "API Test",
    "body": "This is a test email from the API",
    "isHtml": false
  }')

echo $RESPONSE | jq .

# Extract message ID
MESSAGE_ID=$(echo $RESPONSE | jq -r '.message_id')
DATE=$(date +%Y-%m-%d)

# Check status
echo "3. Checking message status:"
sleep 2
curl -s "$BASE_URL/send/$MESSAGE_ID/status?date=$DATE" | jq .
```

### Debugging Common Issues

**1. SMTP Connection Issues**
```bash
# Check SMTP configuration
curl -s http://localhost:8000/health | jq '.checks.smtp_config'

# Test SMTP connection manually
telnet your-smtp-host 587
```

**2. Queue Processing Issues**
```bash
# Check queue status
composer queue status

# View worker logs
composer queue logs 50

# Process queue manually
composer queue process
```

**3. Rate Limiting Issues**
```bash
# Check current limits
curl -I -X POST http://localhost:8000/send \
  -H "X-Api-Key: your-key" \
  -H "Content-Type: application/json"

# Look for rate limit headers
# X-RateLimit-Limit: 100
# X-RateLimit-Remaining: 85
```

### Load Testing

**Apache Bench (ab) Example**
```bash
# Create test payload
cat > test_email.json << EOF
{
  "to": "load-test@example.com",
  "subject": "Load Test",
  "body": "Load testing email"
}
EOF

# Run load test (10 concurrent, 100 total)
ab -n 100 -c 10 -T application/json \
   -H "X-Api-Key: your-api-key" \
   -p test_email.json \
   http://localhost:8000/send
```

---

## Production Deployment

### Infrastructure Requirements

**Minimum Requirements:**
- **CPU:** 1 vCPU
- **RAM:** 512MB
- **Storage:** 5GB SSD
- **Network:** 1 Mbps

**Recommended Production:**
- **CPU:** 2+ vCPUs
- **RAM:** 2GB+
- **Storage:** 20GB SSD
- **Network:** 10 Mbps
- **Load Balancer:** For high availability

### Docker Deployment

**docker-compose.production.yml**
```yaml
version: '3.8'
services:
  aegis-mailer:
    image: aegis/mailer:1.0.0
    restart: unless-stopped
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
      - API_KEY=${API_KEY}
      - SMTP_HOST=${SMTP_HOST}
      - SMTP_PORT=${SMTP_PORT}
      - SMTP_USERNAME=${SMTP_USERNAME}
      - SMTP_PASSWORD=${SMTP_PASSWORD}
      - WEBHOOK_URL=${WEBHOOK_URL}
      - WEBHOOK_SECRET=${WEBHOOK_SECRET}
    ports:
      - "8080:80"
    volumes:
      - ./var/logs:/app/var/logs
      - ./var/queue:/app/var/queue
      - ./var/delivery:/app/var/delivery
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost/health"]
      interval: 30s
      timeout: 10s
      retries: 3
  
  worker:
    image: aegis/mailer:1.0.0
    restart: unless-stopped
    command: php bin/queue start --daemon
    environment:
      - APP_ENV=production
      - SMTP_HOST=${SMTP_HOST}
      - SMTP_PORT=${SMTP_PORT}
      - SMTP_USERNAME=${SMTP_USERNAME}
      - SMTP_PASSWORD=${SMTP_PASSWORD}
      - WEBHOOK_URL=${WEBHOOK_URL}
      - WEBHOOK_SECRET=${WEBHOOK_SECRET}
    volumes:
      - ./var/logs:/app/var/logs
      - ./var/queue:/app/var/queue
      - ./var/delivery:/app/var/delivery
    depends_on:
      - aegis-mailer

  nginx:
    image: nginx:alpine
    restart: unless-stopped
    ports:
      - "443:443"
      - "80:80"
    volumes:
      - ./nginx.conf:/etc/nginx/nginx.conf
      - ./ssl:/etc/nginx/ssl
    depends_on:
      - aegis-mailer
```

### Nginx Configuration

**nginx.conf**
```nginx
events {
    worker_connections 1024;
}

http {
    upstream aegis_mailer {
        server aegis-mailer:80;
    }
    
    # Rate limiting
    limit_req_zone $binary_remote_addr zone=api:10m rate=10r/s;
    
    server {
        listen 80;
        server_name your-domain.com;
        return 301 https://$server_name$request_uri;
    }
    
    server {
        listen 443 ssl http2;
        server_name your-domain.com;
        
        ssl_certificate /etc/nginx/ssl/cert.pem;
        ssl_certificate_key /etc/nginx/ssl/key.pem;
        
        location / {
            limit_req zone=api burst=20 nodelay;
            
            proxy_pass http://aegis_mailer;
            proxy_set_header Host $host;
            proxy_set_header X-Real-IP $remote_addr;
            proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
            proxy_set_header X-Forwarded-Proto $scheme;
            
            # Timeouts
            proxy_connect_timeout 30s;
            proxy_send_timeout 30s;
            proxy_read_timeout 30s;
        }
        
        # Health check endpoint (no rate limit)
        location /health {
            proxy_pass http://aegis_mailer;
            access_log off;
        }
    }
}
```

### Monitoring & Alerting

**Health Check Monitoring**
```bash
#!/bin/bash
# health-monitor.sh

AEGIS_URL="https://your-domain.com"
WEBHOOK_URL="https://your-monitoring-service.com/webhook"

response=$(curl -s -o /dev/null -w "%{http_code}" "$AEGIS_URL/health")

if [ "$response" != "200" ]; then
    curl -X POST "$WEBHOOK_URL" \
         -H "Content-Type: application/json" \
         -d "{\"alert\": \"Aegis Mailer health check failed\", \"status\": \"$response\"}"
fi
```

**Log Monitoring with ELK Stack**
```yaml
# filebeat.yml
filebeat.inputs:
- type: log
  enabled: true
  paths:
    - /app/var/logs/*.log
  fields:
    service: aegis-mailer
  fields_under_root: true

output.logstash:
  hosts: ["logstash:5044"]
```

### Backup & Recovery

**Automated Backup Script**
```bash
#!/bin/bash
# backup-aegis.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backup/aegis-mailer"
APP_DIR="/app"

# Create backup directory
mkdir -p "$BACKUP_DIR/$DATE"

# Backup configuration
cp "$APP_DIR/.env" "$BACKUP_DIR/$DATE/"

# Backup delivery tracking data
tar -czf "$BACKUP_DIR/$DATE/delivery-data.tar.gz" -C "$APP_DIR" var/delivery/

# Backup logs (last 7 days)
find "$APP_DIR/var/logs" -name "*.log" -mtime -7 | \
    tar -czf "$BACKUP_DIR/$DATE/logs.tar.gz" -T -

# Clean old backups (keep 30 days)
find "$BACKUP_DIR" -type d -mtime +30 -exec rm -rf {} \;

echo "Backup completed: $BACKUP_DIR/$DATE"
```

### Security Hardening

**Environment Security**
```bash
# Set secure permissions
chmod 600 .env
chown www-data:www-data .env

# Secure log directory
chmod 750 var/logs
chown -R www-data:www-data var/

# Secure queue directory
chmod 750 var/queue
chown -R www-data:www-data var/queue/
```

**API Key Management**
```bash
# Generate secure API keys
openssl rand -hex 32

# Rotate API keys regularly
# Update .env and restart services
docker-compose restart
```

---

## Conclusion

Aegis Mailer provides a robust, secure, and scalable email delivery solution. This documentation covers the complete integration process from basic email sending to production deployment.

For additional support:
- **GitHub Issues:** [Report bugs and feature requests](https://github.com/amiano4/aegis-mailer/issues)
- **Documentation:** [Latest updates](https://github.com/amiano4/aegis-mailer)
- **Community:** [Discussions and help](https://github.com/amiano4/aegis-mailer/discussions)

---

**Last Updated:** August 2024  
**API Version:** 1.0.0  
**Document Version:** 1.0.0
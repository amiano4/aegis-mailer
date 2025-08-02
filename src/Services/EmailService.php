<?php

declare(strict_types=1);

namespace PHPMailService\Services;

use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailService\Config\EmailConfig;
use PHPMailService\Contracts\EmailServiceInterface;
use PHPMailService\Exceptions\EmailException;
use PHPMailService\Models\EmailMessage;
use PHPMailService\Models\EmailResult;
use PHPMailService\Utils\Logger;

/**
 * Core Email Service
 *
 * Simple, reliable email sending service focused on POST request handling
 */
class EmailService implements EmailServiceInterface
{
    private PHPMailer $mailer;
    private EmailConfig $config;

    public function __construct(?EmailConfig $config = null)
    {
        $this->config = $config ?? EmailConfig::fromEnvironment();
        $this->mailer = new PHPMailer(true);
        $this->configurePHPMailer();
    }

    /**
     * Send a single email - CORE FUNCTIONALITY
     */
    public function send(EmailMessage $message): EmailResult
    {
        try {
            $this->prepareMailer($message);
            $this->mailer->send();

            $result = new EmailResult(
                $this->mailer->getLastMessageID(),
                $message->getId(),
                true,
                null,
                [
                    'to' => $message->getTo(),
                    'subject' => $message->getSubject(),
                    'from' => $this->config->get('from.address'),
                ]
            );

            Logger::logEmailSent(
                $result->getMessageId(),
                $message->getTo(),
                $message->getSubject()
            );

            return $result;

        } catch (PHPMailerException $e) {
            $error = "Email sending failed: " . $e->getMessage();

            Logger::logEmailFailed(
                $message->getTo(),
                $message->getSubject(),
                $error
            );

            return new EmailResult(
                '',
                $message->getId(),
                false,
                $error
            );
        }
    }

    /**
     * Send from POST data - MAIN PURPOSE: Handle POST requests
     */
    public function sendFromPost(array $data): EmailResult
    {
        // Validate required fields
        if (empty($data['to'])) {
            throw new EmailException('Recipient email address is required');
        }
        if (empty($data['subject'])) {
            throw new EmailException('Email subject is required');
        }
        if (empty($data['body'])) {
            throw new EmailException('Email body is required');
        }

        // Create message from POST data
        $message = new EmailMessage($data['to'], $data['subject'], $data['body']);

        // Set optional fields
        if (! empty($data['to_name'])) {
            $message->setTo($data['to'], $data['to_name']);
        }

        if (isset($data['html']) && $data['html']) {
            $message->setBody($data['body'], true);
            if (! empty($data['text'])) {
                $message->setTextBody($data['text']);
            }
        }

        // Handle CC recipients
        if (! empty($data['cc'])) {
            $ccList = is_array($data['cc']) ? $data['cc'] : [$data['cc']];
            foreach ($ccList as $cc) {
                $message->addCc($cc);
            }
        }

        // Handle BCC recipients
        if (! empty($data['bcc'])) {
            $bccList = is_array($data['bcc']) ? $data['bcc'] : [$data['bcc']];
            foreach ($bccList as $bcc) {
                $message->addBcc($bcc);
            }
        }

        // Handle reply-to
        if (! empty($data['reply_to'])) {
            $message->setReplyTo($data['reply_to']);
        }

        // Handle attachments (if feature enabled)
        if ($this->config->isFeatureEnabled('attachments_enabled') && ! empty($data['attachments'])) {
            foreach ($data['attachments'] as $attachment) {
                if (! empty($attachment['path']) && file_exists($attachment['path'])) {
                    $message->addAttachment(
                        $attachment['path'],
                        $attachment['name'] ?? null
                    );
                }
            }
        }

        return $this->send($message);
    }

    public function sendBatch(array $messages): array
    {
        $results = [];
        foreach ($messages as $message) {
            $results[] = $this->send($message);
        }

        return $results;
    }

    public function queue(EmailMessage $message): string
    {
        // Simple queue implementation - for now just send immediately
        // Can be extended with actual queue systems later
        $result = $this->send($message);

        return $result->getEmailId();
    }

    public function validateConfiguration(): bool
    {
        try {
            $this->mailer->smtpConnect();
            $this->mailer->smtpClose();

            return true;
        } catch (PHPMailerException $e) {
            return false;
        }
    }

    public function getHealthStatus(): array
    {
        $healthy = $this->validateConfiguration();

        return [
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'smtp_connection' => $healthy,
            'timestamp' => date('Y-m-d H:i:s'),
            'config' => [
                'host' => $this->config->get('smtp.host'),
                'port' => $this->config->get('smtp.port'),
                'encryption' => $this->config->get('smtp.encryption'),
            ],
        ];
    }

    private function configurePHPMailer(): void
    {
        $this->mailer->isSMTP();
        $this->mailer->Host = $this->config->get('smtp.host');
        $this->mailer->SMTPAuth = $this->config->get('smtp.auth');
        $this->mailer->Username = $this->config->get('smtp.username');
        $this->mailer->Password = $this->config->get('smtp.password');
        $this->mailer->Port = $this->config->get('smtp.port');

        $encryption = $this->config->get('smtp.encryption');
        if ($encryption === 'tls') {
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($encryption === 'ssl') {
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }

        $this->mailer->Timeout = $this->config->get('smtp.timeout');
        $this->mailer->setFrom(
            $this->config->get('from.address'),
            $this->config->get('from.name')
        );

        // Deliverability headers
        $appName = $this->config->get('app.name', 'Professional Email Service');
        $appVersion = $this->config->get('app.version', '1.0.0');
        $fromDomain = $this->extractDomain($this->config->get('from.address'));

        $this->mailer->addCustomHeader('X-Mailer', $appName . ' v' . $appVersion);
        $this->mailer->addCustomHeader('X-Priority', '3');
        $this->mailer->addCustomHeader('List-Unsubscribe', '<mailto:unsubscribe@' . $fromDomain . '>');
        $this->mailer->addCustomHeader('X-Auto-Response-Suppress', 'OOF, DR, RN, NRN, AutoReply');
        $this->mailer->addCustomHeader('Precedence', 'bulk');

        // Add message ID with proper domain
        $this->mailer->MessageID = '<' . uniqid() . '@' . $fromDomain . '>';
    }

    private function prepareMailer(EmailMessage $message): void
    {
        // Clear previous recipients
        $this->mailer->clearAddresses();
        $this->mailer->clearAttachments();
        $this->mailer->clearCCs();
        $this->mailer->clearBCCs();

        // Set recipient
        $this->mailer->addAddress($message->getTo(), $message->getToName());

        // Set subject and body
        $this->mailer->Subject = $message->getSubject();

        if ($message->isHtml()) {
            $this->mailer->isHTML(true);
            $this->mailer->Body = $message->getBody();
            if ($message->getTextBody()) {
                $this->mailer->AltBody = $message->getTextBody();
            }
        } else {
            $this->mailer->isHTML(false);
            $this->mailer->Body = $message->getBody();
        }

        // Add CC recipients
        foreach ($message->getCc() as $cc) {
            $this->mailer->addCC($cc['email'], $cc['name'] ?? '');
        }

        // Add BCC recipients
        foreach ($message->getBcc() as $bcc) {
            $this->mailer->addBCC($bcc['email'], $bcc['name'] ?? '');
        }

        // Set reply-to
        if ($message->getReplyTo()) {
            $this->mailer->addReplyTo($message->getReplyTo(), $message->getReplyToName());
        }

        // Add attachments (if feature enabled)
        if ($this->config->isFeatureEnabled('attachments_enabled')) {
            foreach ($message->getAttachments() as $attachment) {
                $this->mailer->addAttachment(
                    $attachment['path'],
                    $attachment['name']
                );
            }
        }

        // Add custom headers
        foreach ($message->getHeaders() as $name => $value) {
            $this->mailer->addCustomHeader($name, $value);
        }

        // Set priority
        $priority = $message->getPriority();
        $this->mailer->addCustomHeader('X-Priority', (string)$priority);
    }

    /**
     * Extract domain from email address
     */
    private function extractDomain(string $email): string
    {
        $parts = explode('@', $email);

        return $parts[1] ?? 'localhost';
    }
}

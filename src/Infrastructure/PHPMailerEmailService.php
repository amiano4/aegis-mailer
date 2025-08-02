<?php

declare(strict_types=1);

namespace Aegis\Infrastructure;

use Aegis\Domain\EmailMessage;
use Aegis\Domain\EmailResult;
use Aegis\Domain\EmailServiceInterface;
use HTMLPurifier;
use PHPMailer\PHPMailer\PHPMailer;
use Psr\Log\LoggerInterface;

final class PHPMailerEmailService implements EmailServiceInterface
{
    public function __construct(
        private readonly PHPMailer $mailer,
        private readonly LoggerInterface $logger,
        private readonly HTMLPurifier $htmlPurifier
    ) {}

    public function send(EmailMessage $message): EmailResult
    {
        try {
            $this->prepareMailer($message);
            $this->mailer->send();

            $result = new EmailResult(
                true,
                $this->mailer->getLastMessageID(),
                null,
                [
                    'to' => $message->to,
                    'to_name' => $message->toName,
                    'subject' => $message->subject,
                    'is_html' => $message->isHtml,
                    'body_length' => strlen($message->body),
                    'body_preview' => substr(strip_tags($message->body), 0, 100),
                    'cc' => $message->cc,
                    'bcc' => array_map(fn($bcc) => $bcc['email'] ?? $bcc, $message->bcc), // Hide names, keep emails
                    'reply_to' => $message->replyTo,
                    'attachments' => array_map(fn($att) => ['name' => $att['name'], 'size' => strlen($att['content'])], $message->attachments),
                    'priority' => $message->priority,
                    'headers' => $message->headers,
                    'smtp_message_id' => $this->mailer->getLastMessageID()
                ]
            );

            $this->logger->info('Email sent successfully', $result->toArray());

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Email sending failed', [
                'error' => $e->getMessage(),
                'exception_class' => get_class($e),
                'to' => $message->to,
                'to_name' => $message->toName,
                'subject' => $message->subject,
                'is_html' => $message->isHtml,
                'body_length' => strlen($message->body),
                'body_preview' => substr(strip_tags($message->body), 0, 100),
                'cc_count' => count($message->cc),
                'bcc_count' => count($message->bcc),
                'reply_to' => $message->replyTo,
                'attachments_count' => count($message->attachments),
                'priority' => $message->priority,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'smtp_config' => [
                    'host' => $this->mailer->Host,
                    'port' => $this->mailer->Port,
                    'auth' => $this->mailer->SMTPAuth,
                    'secure' => $this->mailer->SMTPSecure
                ]
            ]);

            return new EmailResult(false, null, $e->getMessage());
        }
    }

    private function prepareMailer(EmailMessage $message): void
    {
        $this->mailer->clearAddresses();
        $this->mailer->clearAttachments();
        $this->mailer->clearCCs();
        $this->mailer->clearBCCs();

        $this->mailer->addAddress($message->to, $message->toName ?? '');

        foreach ($message->cc as $cc) {
            $this->mailer->addCC($cc['email'], $cc['name'] ?? '');
        }

        foreach ($message->bcc as $bcc) {
            $this->mailer->addBCC($bcc['email'], $bcc['name'] ?? '');
        }

        if ($message->replyTo) {
            $this->mailer->addReplyTo($message->replyTo);
        }

        $this->mailer->Subject = $message->subject;

        if ($message->isHtml) {
            $this->mailer->isHTML(true);
            $this->mailer->Body = $this->htmlPurifier->purify($message->body);
            $this->mailer->AltBody = strip_tags($message->body);
        } else {
            $this->mailer->isHTML(false);
            $this->mailer->Body = $message->body;
        }

        foreach ($message->attachments as $attachment) {
            $this->mailer->addStringAttachment($attachment['content'], $attachment['name']);
        }

        foreach ($message->headers as $name => $value) {
            $this->mailer->addCustomHeader($name, $value);
        }

        $this->mailer->Priority = $message->priority;
    }
}

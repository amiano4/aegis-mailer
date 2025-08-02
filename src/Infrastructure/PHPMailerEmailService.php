<?php

declare(strict_types=1);

namespace Aegis\Infrastructure;

use Aegis\Domain\EmailMessage;
use Aegis\Domain\EmailResult;
use Aegis\Domain\EmailServiceInterface;
use Aegis\Domain\DeliveryTracker;
use Aegis\Domain\WebhookNotifier;
use HTMLPurifier;
use PHPMailer\PHPMailer\PHPMailer;
use Psr\Log\LoggerInterface;

final class PHPMailerEmailService implements EmailServiceInterface
{
    public function __construct(
        private readonly PHPMailer $mailer,
        private readonly LoggerInterface $logger,
        private readonly HTMLPurifier $htmlPurifier,
        private readonly DeliveryTracker $deliveryTracker,
        private readonly WebhookNotifier $webhookNotifier
    ) {}

    public function send(EmailMessage $message, ?string $messageId = null): EmailResult
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

            // Notify success via webhook
            if ($messageId) {
                $this->webhookNotifier->notifySuccess($messageId);
            }

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

            // Record failure and notify via webhook
            if ($messageId) {
                $this->deliveryTracker->recordFailure($messageId, $e->getMessage(), 1);
                $this->webhookNotifier->notifyFailure($messageId, $e->getMessage(), 1);
            }

            return new EmailResult(false, null, $e->getMessage());
        }
    }

    private function prepareMailer(EmailMessage $message): void
    {
        $this->mailer->clearAddresses();
        $this->mailer->clearAttachments();
        $this->mailer->clearCCs();
        $this->mailer->clearBCCs();
        $this->mailer->CharSet = 'UTF-8';

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

        // Clean and normalize UTF-8 content
        $cleanSubject = $this->cleanUtf8($message->subject);
        $cleanBody = $this->cleanUtf8($message->body);
        
        $this->mailer->Subject = $cleanSubject;

        if ($message->isHtml) {
            $this->mailer->isHTML(true);
            
            // Extract body content from React Email full HTML document
            $bodyContent = $this->extractBodyContent($cleanBody);
            
            // Use custom minimal cleaning instead of HTMLPurifier to preserve formatting
            $this->mailer->Body = $this->minimalHtmlClean($bodyContent);
            $this->mailer->AltBody = strip_tags($bodyContent);
        } else {
            $this->mailer->isHTML(false);
            $this->mailer->Body = $cleanBody;
        }

        foreach ($message->attachments as $attachment) {
            $this->mailer->addStringAttachment($attachment['content'], $attachment['name']);
        }

        foreach ($message->headers as $name => $value) {
            $this->mailer->addCustomHeader($name, $value);
        }

        $this->mailer->Priority = $message->priority;
    }

    private function cleanUtf8(string $text): string
    {
        // Preserve base64 image data URIs during cleaning
        $imageMatches = [];
        preg_match_all('/data:image\/[^;]+;base64,[A-Za-z0-9+\/=]+/', $text, $imageMatches);
        $imageReplacements = [];
        
        foreach ($imageMatches[0] as $i => $match) {
            $placeholder = "___IMAGE_PLACEHOLDER_{$i}___";
            $imageReplacements[$placeholder] = $match;
            $text = str_replace($match, $placeholder, $text);
        }
        
        // Remove BOM (Byte Order Mark)
        $text = str_replace("\xEF\xBB\xBF", '', $text);
        
        // Remove invisible Unicode characters that cause encoding issues
        $text = preg_replace('/[\x{200B}-\x{200F}\x{2028}-\x{202F}\x{205F}-\x{206F}\x{FEFF}]/u', '', $text);
        
        // Remove zero-width characters and other problematic Unicode
        $text = preg_replace('/[\x{00AD}\x{034F}\x{061C}\x{180E}\x{2060}\x{2061}\x{2062}\x{2063}\x{2064}]/u', '', $text);
        
        // Ensure proper UTF-8 encoding
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'auto');
        }
        
        // Clean any remaining encoding artifacts
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        
        // Restore base64 image data
        foreach ($imageReplacements as $placeholder => $originalImage) {
            $text = str_replace($placeholder, $originalImage, $text);
        }
        
        return $text;
    }

    private function extractBodyContent(string $html): string
    {
        // If it's a full HTML document from React Email, extract just the body content
        if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $html, $matches)) {
            return $matches[1];
        }
        
        // If there's no body tag, but there are html/head tags, try to extract content after head
        if (preg_match('/<\/head\s*>(.*?)(?:<\/html>|$)/is', $html, $matches)) {
            return $matches[1];
        }
        
        // Remove any remaining html/head/meta/title tags that might be at the beginning
        $html = preg_replace('/<(?:!DOCTYPE|html|head|meta|title)[^>]*>/i', '', $html);
        $html = preg_replace('/<\/(?:html|head|title)>/i', '', $html);
        
        return $html;
    }

    private function minimalHtmlClean(string $html): string
    {
        // Remove ALL invisible Unicode characters that spam filters flag
        $html = preg_replace('/[\x{00AD}\x{034F}\x{061C}\x{115F}\x{1160}\x{17B4}\x{17B5}\x{180B}-\x{180F}\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2060}-\x{2064}\x{2066}-\x{206F}\x{3164}\x{FE00}-\x{FE0F}\x{FEFF}\x{FFA0}\x{1BCA0}-\x{1BCA3}\x{1D173}-\x{1D17A}\x{E0000}-\x{E01EF}]/u', '', $html);
        
        // Remove spam-triggering hidden preview text divs completely
        $html = preg_replace('/<div[^>]*data-skip-in-text[^>]*>.*?<\/div><\/div>/is', '', $html);
        
        // Remove React Email comments that look suspicious
        $html = preg_replace('/<!--\$-->|<!--\/\$-->|<!--\d+-->/is', '', $html);
        
        // Replace unprocessed template variables that look suspicious
        $html = str_replace('{{unsubscribeUrl}}', '#', $html);
        
        // Remove MSO comments that might trigger filters
        $html = preg_replace('/<!--\[if mso\]>.*?<!\[endif\]-->/is', '', $html);
        
        // Only remove truly dangerous elements while preserving all formatting
        $html = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $html);
        $html = preg_replace('/<object\b[^<]*(?:(?!<\/object>)<[^<]*)*<\/object>/mi', '', $html);
        $html = preg_replace('/<embed\b[^>]*>/mi', '', $html);
        $html = preg_replace('/<applet\b[^<]*(?:(?!<\/applet>)<[^<]*)*<\/applet>/mi', '', $html);
        $html = preg_replace('/<form\b[^<]*(?:(?!<\/form>)<[^<]*)*<\/form>/mi', '', $html);
        
        // Remove dangerous event handlers
        $html = preg_replace('/\son[a-z]+\s*=\s*["\'][^"\']*["\']/mi', '', $html);
        
        return $html;
    }
}

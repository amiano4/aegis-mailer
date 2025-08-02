<?php

declare(strict_types=1);

namespace PHPMailService\Models;

use InvalidArgumentException;

/**
 * Email Message Model
 *
 * Represents an email message with all its components
 */
class EmailMessage
{
    private string $to;
    private ?string $toName = null;
    private string $subject;
    private string $body;
    private bool $isHtml = false;
    private ?string $textBody = null;
    private array $cc = [];
    private array $bcc = [];
    private ?string $replyTo = null;
    private ?string $replyToName = null;
    private array $attachments = [];
    private array $headers = [];
    private int $priority = 3; // 1=High, 3=Normal, 5=Low
    private ?string $templateName = null;
    private array $templateData = [];
    private string $id;

    public function __construct(string $to, string $subject, string $body)
    {
        $this->setTo($to);
        $this->setSubject($subject);
        $this->setBody($body);
        $this->id = \Ramsey\Uuid\Uuid::uuid4()->toString();
    }

    public function setTo(string $email, ?string $name = null): self
    {
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email address: $email");
        }
        $this->to = $email;
        $this->toName = $name;

        return $this;
    }

    public function setSubject(string $subject): self
    {
        if (empty(trim($subject))) {
            throw new InvalidArgumentException('Subject cannot be empty');
        }
        $this->subject = trim($subject);

        return $this;
    }

    public function setBody(string $body, bool $isHtml = false): self
    {
        if (empty(trim($body))) {
            throw new InvalidArgumentException('Body cannot be empty');
        }
        $this->body = $body;
        $this->isHtml = $isHtml;

        return $this;
    }

    public function setTextBody(string $textBody): self
    {
        $this->textBody = $textBody;

        return $this;
    }

    public function addCc(string $email, ?string $name = null): self
    {
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid CC email address: $email");
        }
        $this->cc[] = ['email' => $email, 'name' => $name];

        return $this;
    }

    public function addBcc(string $email, ?string $name = null): self
    {
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid BCC email address: $email");
        }
        $this->bcc[] = ['email' => $email, 'name' => $name];

        return $this;
    }

    public function setReplyTo(string $email, ?string $name = null): self
    {
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid reply-to email address: $email");
        }
        $this->replyTo = $email;
        $this->replyToName = $name;

        return $this;
    }

    public function addAttachment(string $path, ?string $name = null, ?string $mimeType = null): self
    {
        if (! file_exists($path)) {
            throw new InvalidArgumentException("Attachment file not found: $path");
        }
        $this->attachments[] = [
            'path' => $path,
            'name' => $name ?? basename($path),
            'mimeType' => $mimeType,
        ];

        return $this;
    }

    public function addHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function setPriority(int $priority): self
    {
        if (! in_array($priority, [1, 3, 5])) {
            throw new InvalidArgumentException('Priority must be 1 (High), 3 (Normal), or 5 (Low)');
        }
        $this->priority = $priority;

        return $this;
    }

    public function setTemplate(string $templateName, array $data = []): self
    {
        $this->templateName = $templateName;
        $this->templateData = $data;

        return $this;
    }

    // Getters
    public function getId(): string
    {
        return $this->id;
    }
    public function getTo(): string
    {
        return $this->to;
    }
    public function getToName(): ?string
    {
        return $this->toName;
    }
    public function getSubject(): string
    {
        return $this->subject;
    }
    public function getBody(): string
    {
        return $this->body;
    }
    public function isHtml(): bool
    {
        return $this->isHtml;
    }
    public function getTextBody(): ?string
    {
        return $this->textBody;
    }
    public function getCc(): array
    {
        return $this->cc;
    }
    public function getBcc(): array
    {
        return $this->bcc;
    }
    public function getReplyTo(): ?string
    {
        return $this->replyTo;
    }
    public function getReplyToName(): ?string
    {
        return $this->replyToName;
    }
    public function getAttachments(): array
    {
        return $this->attachments;
    }
    public function getHeaders(): array
    {
        return $this->headers;
    }
    public function getPriority(): int
    {
        return $this->priority;
    }
    public function getTemplateName(): ?string
    {
        return $this->templateName;
    }
    public function getTemplateData(): array
    {
        return $this->templateData;
    }

    /**
     * Convert message to array for serialization
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'to' => $this->to,
            'to_name' => $this->toName,
            'subject' => $this->subject,
            'body' => $this->body,
            'is_html' => $this->isHtml,
            'text_body' => $this->textBody,
            'cc' => $this->cc,
            'bcc' => $this->bcc,
            'reply_to' => $this->replyTo,
            'reply_to_name' => $this->replyToName,
            'attachments' => $this->attachments,
            'headers' => $this->headers,
            'priority' => $this->priority,
            'template_name' => $this->templateName,
            'template_data' => $this->templateData,
        ];
    }

    /**
     * Create message from array
     */
    public static function fromArray(array $data): self
    {
        $message = new self($data['to'], $data['subject'], $data['body']);

        if (isset($data['to_name'])) {
            $message->toName = $data['to_name'];
        }
        if (isset($data['is_html'])) {
            $message->isHtml = $data['is_html'];
        }
        if (isset($data['text_body'])) {
            $message->textBody = $data['text_body'];
        }
        if (isset($data['cc'])) {
            $message->cc = $data['cc'];
        }
        if (isset($data['bcc'])) {
            $message->bcc = $data['bcc'];
        }
        if (isset($data['reply_to'])) {
            $message->replyTo = $data['reply_to'];
        }
        if (isset($data['reply_to_name'])) {
            $message->replyToName = $data['reply_to_name'];
        }
        if (isset($data['attachments'])) {
            $message->attachments = $data['attachments'];
        }
        if (isset($data['headers'])) {
            $message->headers = $data['headers'];
        }
        if (isset($data['priority'])) {
            $message->priority = $data['priority'];
        }
        if (isset($data['template_name'])) {
            $message->templateName = $data['template_name'];
        }
        if (isset($data['template_data'])) {
            $message->templateData = $data['template_data'];
        }
        if (isset($data['id'])) {
            $message->id = $data['id'];
        }

        return $message;
    }
}

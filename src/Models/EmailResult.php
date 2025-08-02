<?php

declare(strict_types=1);

namespace PHPMailService\Models;

/**
 * Email Result Model
 *
 * Represents the result of an email sending operation
 */
class EmailResult
{
    private string $messageId;
    private string $emailId;
    private bool $success;
    private ?string $error = null;
    private array $metadata = [];
    private \DateTimeImmutable $sentAt;

    public function __construct(
        string $messageId,
        string $emailId,
        bool $success,
        ?string $error = null,
        array $metadata = []
    ) {
        $this->messageId = $messageId;
        $this->emailId = $emailId;
        $this->success = $success;
        $this->error = $error;
        $this->metadata = $metadata;
        $this->sentAt = new \DateTimeImmutable();
    }

    public function getMessageId(): string
    {
        return $this->messageId;
    }

    public function getEmailId(): string
    {
        return $this->emailId;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getSentAt(): \DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function addMetadata(string $key, mixed $value): self
    {
        $this->metadata[$key] = $value;

        return $this;
    }

    /**
     * Convert result to array for JSON response
     */
    public function toArray(): array
    {
        return [
            'message_id' => $this->messageId,
            'email_id' => $this->emailId,
            'success' => $this->success,
            'error' => $this->error,
            'metadata' => $this->metadata,
            'sent_at' => $this->sentAt->format('Y-m-d H:i:s'),
            'sent_at_iso' => $this->sentAt->format(\DateTimeInterface::ISO8601),
        ];
    }
}

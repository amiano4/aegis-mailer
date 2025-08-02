<?php

declare(strict_types=1);

namespace PHPMailService\Contracts;

use PHPMailService\Models\EmailMessage;
use PHPMailService\Models\EmailResult;

/**
 * Email Service Contract
 *
 * Defines the interface for email service implementations
 */
interface EmailServiceInterface
{
    /**
     * Send an email message
     *
     * @param EmailMessage $message The email message to send
     * @return EmailResult The result of the email sending operation
     * @throws \PHPMailService\Exceptions\EmailException
     */
    public function send(EmailMessage $message): EmailResult;

    /**
     * Send multiple email messages
     *
     * @param EmailMessage[] $messages Array of email messages
     * @return EmailResult[] Array of email results
     */
    public function sendBatch(array $messages): array;

    /**
     * Queue an email for later delivery
     *
     * @param EmailMessage $message The email message to queue
     * @return string The queue job ID
     */
    public function queue(EmailMessage $message): string;

    /**
     * Validate email configuration
     *
     * @return bool True if configuration is valid
     */
    public function validateConfiguration(): bool;

    /**
     * Get service health status
     *
     * @return array Health status information
     */
    public function getHealthStatus(): array;
}

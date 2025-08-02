<?php

declare(strict_types=1);

namespace Aegis\Application;

use Aegis\Domain\EmailMessage;
use Aegis\Domain\EmailServiceInterface;
use Interop\Queue\Message;

final class SendEmailCommandHandler
{
    public function __construct(
        private readonly EmailServiceInterface $emailService
    ) {}

    public function handle(SendEmailCommand $command, ?string $messageId = null): void
    {
        $message = new EmailMessage(
            $command->to,
            $command->subject,
            $command->body,
            $command->isHtml,
            $command->toName,
            $command->cc,
            $command->bcc,
            $command->replyTo,
            $command->attachments,
            $command->headers,
            $command->priority
        );

        $this->emailService->send($message, $messageId);
    }
}

<?php

declare(strict_types=1);

namespace Aegis\Application;

final class SendEmailCommand
{
    public function __construct(
        public readonly string $to,
        public readonly string $subject,
        public readonly string $body,
        public readonly bool $isHtml = false,
        public readonly ?string $toName = null,
        public readonly array $cc = [],
        public readonly array $bcc = [],
        public readonly ?string $replyTo = null,
        public readonly array $attachments = [],
        public readonly array $headers = [],
        public readonly int $priority = 3
    ) {}
}

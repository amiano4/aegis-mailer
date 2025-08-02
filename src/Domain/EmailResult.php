<?php

declare(strict_types=1);

namespace Aegis\Domain;

final class EmailResult
{
    public function __construct(
        public readonly bool $isSuccess,
        public readonly ?string $messageId,
        public readonly ?string $error = null,
        public readonly array $context = []
    ) {}

    public function toArray(): array
    {
        return [
            'success' => $this->isSuccess,
            'message_id' => $this->messageId,
            'error' => $this->error,
            'context' => $this->context,
        ];
    }
}

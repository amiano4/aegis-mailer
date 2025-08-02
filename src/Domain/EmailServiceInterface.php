<?php

declare(strict_types=1);

namespace Aegis\Domain;

interface EmailServiceInterface
{
    public function send(EmailMessage $message): EmailResult;
}

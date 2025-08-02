<?php

declare(strict_types=1);

namespace Aegis\Application;

use Interop\Queue\Context;
use Interop\Queue\Message;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

final class SendEmailAction
{
    public function __construct(
        private readonly Context $context,
        private readonly LoggerInterface $logger
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $data = (array)$request->getParsedBody();

        try {
            $command = new SendEmailCommand(
                $data['to'] ?? '',
                $data['subject'] ?? '',
                $data['body'] ?? '',
                $data['isHtml'] ?? false,
                $data['toName'] ?? null,
                $data['cc'] ?? [],
                $data['bcc'] ?? [],
                $data['replyTo'] ?? null,
                $data['attachments'] ?? [],
                $data['headers'] ?? [],
                $data['priority'] ?? 3
            );

            $queue = $this->context->createQueue('send-email');
            $message = $this->context->createMessage(serialize($command));
            $message->setMessageId(Uuid::uuid4()->toString());

            $this->context->createProducer()->send($queue, $message);

            $this->logger->info('Email queued for sending', [
                'message_id' => $message->getMessageId(),
                'to' => $command->to,
                'to_name' => $command->toName,
                'subject' => $command->subject,
                'is_html' => $command->isHtml,
                'body_length' => strlen($command->body),
                'body_preview' => substr(strip_tags($command->body), 0, 100),
                'cc_count' => count($command->cc),
                'bcc_count' => count($command->bcc),
                'reply_to' => $command->replyTo,
                'attachments_count' => count($command->attachments),
                'priority' => $command->priority,
                'headers_count' => count($command->headers)
            ]);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Email queued successfully',
                'message_id' => $message->getMessageId(),
            ]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(202);
        } catch (\InvalidArgumentException $e) {
            $this->logger->error('Invalid email data', [
                'error' => $e->getMessage(),
                'request_data' => $data,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        } catch (\Throwable $e) {
            $this->logger->critical('Unhandled error in SendEmailAction', [
                'error' => $e->getMessage(),
                'exception_class' => get_class($e),
                'request_data' => $data,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            $response->getBody()->write(json_encode(['error' => 'Internal Server Error']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}

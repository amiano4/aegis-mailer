<?php

declare(strict_types=1);

namespace PHPMailService\Tests\Unit\Models;

use InvalidArgumentException;
use PHPMailService\Models\EmailMessage;
use PHPUnit\Framework\TestCase;

class EmailMessageTest extends TestCase
{
    public function testCanCreateBasicEmailMessage(): void
    {
        $message = new EmailMessage('test@example.com', 'Test Subject', 'Test Body');

        $this->assertEquals('test@example.com', $message->getTo());
        $this->assertEquals('Test Subject', $message->getSubject());
        $this->assertEquals('Test Body', $message->getBody());
        $this->assertFalse($message->isHtml());
        $this->assertNotEmpty($message->getId());
    }

    public function testValidatesEmailAddress(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email address: invalid-email');

        new EmailMessage('invalid-email', 'Subject', 'Body');
    }

    public function testValidatesEmptySubject(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Subject cannot be empty');

        new EmailMessage('test@example.com', '', 'Body');
    }

    public function testValidatesEmptyBody(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Body cannot be empty');

        new EmailMessage('test@example.com', 'Subject', '');
    }

    public function testCanSetToWithName(): void
    {
        $message = new EmailMessage('test@example.com', 'Subject', 'Body');
        $message->setTo('new@example.com', 'John Doe');

        $this->assertEquals('new@example.com', $message->getTo());
        $this->assertEquals('John Doe', $message->getToName());
    }

    public function testCanSetHtmlBody(): void
    {
        $message = new EmailMessage('test@example.com', 'Subject', '<h1>HTML Body</h1>');
        $message->setBody('<h1>HTML Body</h1>', true);
        $message->setTextBody('Plain text version');

        $this->assertTrue($message->isHtml());
        $this->assertEquals('<h1>HTML Body</h1>', $message->getBody());
        $this->assertEquals('Plain text version', $message->getTextBody());
    }

    public function testCanAddCcRecipients(): void
    {
        $message = new EmailMessage('test@example.com', 'Subject', 'Body');
        $message->addCc('cc1@example.com', 'CC User 1');
        $message->addCc('cc2@example.com');

        $cc = $message->getCc();
        $this->assertCount(2, $cc);
        $this->assertEquals('cc1@example.com', $cc[0]['email']);
        $this->assertEquals('CC User 1', $cc[0]['name']);
        $this->assertEquals('cc2@example.com', $cc[1]['email']);
        $this->assertNull($cc[1]['name']);
    }

    public function testCanAddBccRecipients(): void
    {
        $message = new EmailMessage('test@example.com', 'Subject', 'Body');
        $message->addBcc('bcc1@example.com', 'BCC User 1');
        $message->addBcc('bcc2@example.com');

        $bcc = $message->getBcc();
        $this->assertCount(2, $bcc);
        $this->assertEquals('bcc1@example.com', $bcc[0]['email']);
        $this->assertEquals('BCC User 1', $bcc[0]['name']);
        $this->assertEquals('bcc2@example.com', $bcc[1]['email']);
        $this->assertNull($bcc[1]['name']);
    }

    public function testValidatesCcEmailAddress(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid CC email address: invalid-cc');

        $message = new EmailMessage('test@example.com', 'Subject', 'Body');
        $message->addCc('invalid-cc');
    }

    public function testValidatesBccEmailAddress(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid BCC email address: invalid-bcc');

        $message = new EmailMessage('test@example.com', 'Subject', 'Body');
        $message->addBcc('invalid-bcc');
    }

    public function testCanSetReplyTo(): void
    {
        $message = new EmailMessage('test@example.com', 'Subject', 'Body');
        $message->setReplyTo('reply@example.com', 'Reply Handler');

        $this->assertEquals('reply@example.com', $message->getReplyTo());
        $this->assertEquals('Reply Handler', $message->getReplyToName());
    }

    public function testValidatesReplyToEmailAddress(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid reply-to email address: invalid-reply');

        $message = new EmailMessage('test@example.com', 'Subject', 'Body');
        $message->setReplyTo('invalid-reply');
    }

    public function testCanAddAttachments(): void
    {
        $testFile = tempnam(sys_get_temp_dir(), 'test_attachment');
        file_put_contents($testFile, 'Test file content');

        $message = new EmailMessage('test@example.com', 'Subject', 'Body');
        $message->addAttachment($testFile, 'test.txt', 'text/plain');

        $attachments = $message->getAttachments();
        $this->assertCount(1, $attachments);
        $this->assertEquals($testFile, $attachments[0]['path']);
        $this->assertEquals('test.txt', $attachments[0]['name']);
        $this->assertEquals('text/plain', $attachments[0]['mimeType']);

        unlink($testFile);
    }

    public function testValidatesAttachmentFileExists(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Attachment file not found: /non/existent/file.txt');

        $message = new EmailMessage('test@example.com', 'Subject', 'Body');
        $message->addAttachment('/non/existent/file.txt');
    }

    public function testCanAddCustomHeaders(): void
    {
        $message = new EmailMessage('test@example.com', 'Subject', 'Body');
        $message->addHeader('X-Custom-Header', 'Custom Value');
        $message->addHeader('X-Another-Header', 'Another Value');

        $headers = $message->getHeaders();
        $this->assertEquals('Custom Value', $headers['X-Custom-Header']);
        $this->assertEquals('Another Value', $headers['X-Another-Header']);
    }

    public function testCanSetPriority(): void
    {
        $message = new EmailMessage('test@example.com', 'Subject', 'Body');

        $message->setPriority(1); // High
        $this->assertEquals(1, $message->getPriority());

        $message->setPriority(5); // Low
        $this->assertEquals(5, $message->getPriority());
    }

    public function testValidatesPriorityValues(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Priority must be 1 (High), 3 (Normal), or 5 (Low)');

        $message = new EmailMessage('test@example.com', 'Subject', 'Body');
        $message->setPriority(7); // Invalid priority
    }

    public function testCanSetTemplate(): void
    {
        $message = new EmailMessage('test@example.com', 'Subject', 'Body');
        $message->setTemplate('welcome', ['name' => 'John', 'company' => 'ACME Corp']);

        $this->assertEquals('welcome', $message->getTemplateName());
        $this->assertEquals(['name' => 'John', 'company' => 'ACME Corp'], $message->getTemplateData());
    }

    public function testCanConvertToArray(): void
    {
        $message = new EmailMessage('test@example.com', 'Subject', 'Body');
        $message->setTo('test@example.com', 'Test User');
        $message->addCc('cc@example.com');
        $message->setPriority(1);

        $array = $message->toArray();

        $this->assertEquals('test@example.com', $array['to']);
        $this->assertEquals('Test User', $array['to_name']);
        $this->assertEquals('Subject', $array['subject']);
        $this->assertEquals('Body', $array['body']);
        $this->assertFalse($array['is_html']);
        $this->assertEquals(1, $array['priority']);
        $this->assertCount(1, $array['cc']);
    }

    public function testCanCreateFromArray(): void
    {
        $data = [
            'to' => 'test@example.com',
            'to_name' => 'Test User',
            'subject' => 'Test Subject',
            'body' => 'Test Body',
            'is_html' => true,
            'cc' => [['email' => 'cc@example.com', 'name' => 'CC User']],
            'priority' => 1,
        ];

        $message = EmailMessage::fromArray($data);

        $this->assertEquals('test@example.com', $message->getTo());
        $this->assertEquals('Test User', $message->getToName());
        $this->assertEquals('Test Subject', $message->getSubject());
        $this->assertEquals('Test Body', $message->getBody());
        $this->assertTrue($message->isHtml());
        $this->assertEquals(1, $message->getPriority());
        $this->assertCount(1, $message->getCc());
    }
}

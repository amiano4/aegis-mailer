<?php

declare(strict_types=1);

namespace PHPMailService\Tests\Unit\Utils;

use PHPMailService\Utils\DeliverabilityChecker;
use PHPUnit\Framework\TestCase;

class DeliverabilityCheckerTest extends TestCase
{
    public function testCheckSpamRiskLowRisk(): void
    {
        $subject = 'Professional Business Update';
        $body = 'Dear customer, we are writing to inform you about our service updates.';

        $result = DeliverabilityChecker::checkSpamRisk($subject, $body);

        $this->assertEquals('low', $result['risk']);
        $this->assertEmpty($result['issues']);
        $this->assertEquals(0, $result['spam_word_count']);
        $this->assertNotEmpty($result['recommendations']);
    }

    public function testCheckSpamRiskMediumRisk(): void
    {
        $subject = 'FREE Money!!! Click HERE NOW!!!';
        $body = 'This is an AMAZING offer with GUARANTEED results!';

        $result = DeliverabilityChecker::checkSpamRisk($subject, $body);

        $this->assertEquals('high', $result['risk']);
        $this->assertNotEmpty($result['issues']);
        $this->assertGreaterThan(0, $result['spam_word_count']);
        $this->assertContains('Spam word detected in subject: \'free\'', $result['issues']);
        $this->assertContains('Excessive capitalization in subject', $result['issues']);
        $this->assertContains('Multiple exclamation marks in subject', $result['issues']);
    }

    public function testCheckSpamRiskHighRisk(): void
    {
        $subject = 'URGENT: FREE CASH WINNER!!!';
        $body = 'Congratulations! You are a WINNER! Click here now for your FREE cash prize! 100% guaranteed! No risk! Act now! Limited time offer! Amazing opportunity! Incredible deal! Miracle solution!';

        $result = DeliverabilityChecker::checkSpamRisk($subject, $body);

        $this->assertEquals('high', $result['risk']);
        $this->assertGreaterThan(5, $result['spam_word_count']);
        $this->assertNotEmpty($result['issues']);
    }

    public function testCheckSpamRiskExcessiveLinks(): void
    {
        $subject = 'Business Update';
        $body = 'Check out these links: https://link1.com https://link2.com https://link3.com https://link4.com https://link5.com';

        $result = DeliverabilityChecker::checkSpamRisk($subject, $body);

        $this->assertEquals('high', $result['risk']);
        $this->assertContains('Too many links in email body', $result['issues']);
    }

    public function testSuggestImprovementsSubjectTooLong(): void
    {
        $subject = 'This is a very long subject line that exceeds the recommended length for email subjects and should trigger a warning';
        $body = 'This is a proper email body with sufficient content to be considered professional.';

        $suggestions = DeliverabilityChecker::suggestImprovements($subject, $body);

        $this->assertContains('Shorten subject line (current: ' . strlen($subject) . ' chars, recommended: <50)', $suggestions);
    }

    public function testSuggestImprovementsSubjectTooShort(): void
    {
        $subject = 'Hi';
        $body = 'This is a proper email body with sufficient content.';

        $suggestions = DeliverabilityChecker::suggestImprovements($subject, $body);

        $this->assertContains('Subject line is too short (current: 2 chars, recommended: 10-50)', $suggestions);
    }

    public function testSuggestImprovementsBodyTooShort(): void
    {
        $subject = 'Good Subject Length';
        $body = 'Short';

        $suggestions = DeliverabilityChecker::suggestImprovements($subject, $body);

        $this->assertContains('Email body is very short, consider adding more content', $suggestions);
    }

    public function testSuggestImprovementsMissingUnsubscribe(): void
    {
        $subject = 'Business Update';
        $body = 'Hello, this is a test email with sufficient content for testing purposes.';

        $suggestions = DeliverabilityChecker::suggestImprovements($subject, $body);

        $found = false;
        foreach ($suggestions as $suggestion) {
            if (strpos($suggestion, 'unsubscribe') !== false) {
                $found = true;

                break;
            }
        }
        $this->assertTrue($found, 'Should suggest adding unsubscribe link. Got: ' . implode(', ', $suggestions));
    }

    public function testSuggestImprovementsMissingAddress(): void
    {
        $subject = 'Business Update';
        $body = 'This is a professional email without business location information.';

        $suggestions = DeliverabilityChecker::suggestImprovements($subject, $body);

        $found = false;
        foreach ($suggestions as $suggestion) {
            if (strpos($suggestion, 'address') !== false) {
                $found = true;

                break;
            }
        }
        $this->assertTrue($found, 'Should suggest adding business address. Got: ' . implode(', ', $suggestions));
    }

    public function testCreateProfessionalTemplate(): void
    {
        $content = '<h2>Welcome!</h2><p>Thank you for joining us.</p>';
        $companyName = 'Test Company';
        $companyDomain = 'test.com';
        $companyWebsite = 'https://test.com';

        $template = DeliverabilityChecker::createProfessionalTemplate(
            $content,
            $companyName,
            $companyDomain,
            $companyWebsite
        );

        $this->assertStringContainsString($content, $template);
        $this->assertStringContainsString($companyName, $template);
        $this->assertStringContainsString($companyDomain, $template);
        $this->assertStringContainsString($companyWebsite, $template);
        $this->assertStringContainsString('unsubscribe@' . $companyDomain, $template);
        $this->assertStringContainsString(date('Y'), $template);
        $this->assertStringContainsString('font-family: Arial', $template);
    }

    public function testCreateProfessionalTemplateWithDefaults(): void
    {
        $content = '<p>Test content</p>';

        $template = DeliverabilityChecker::createProfessionalTemplate($content);

        $this->assertStringContainsString('Your Company Name', $template);
        $this->assertStringContainsString('example.com', $template);
        $this->assertStringContainsString('https://example.com', $template);
        $this->assertStringContainsString('unsubscribe@example.com', $template);
    }

    public function testSpamWordDetection(): void
    {
        $spamWords = ['free', 'winner', 'cash', 'urgent', 'guaranteed', 'amazing'];

        foreach ($spamWords as $word) {
            $result = DeliverabilityChecker::checkSpamRisk(
                "Subject with $word",
                "Body content"
            );

            $this->assertGreaterThanOrEqual(
                1,
                $result['spam_word_count'],
                "Should detect spam word: $word"
            );
        }
    }

    public function testCaseInsensitiveSpamDetection(): void
    {
        $result1 = DeliverabilityChecker::checkSpamRisk('FREE Money', 'Body');
        $result2 = DeliverabilityChecker::checkSpamRisk('free money', 'Body');
        $result3 = DeliverabilityChecker::checkSpamRisk('Free Money', 'Body');

        $this->assertEquals($result1['spam_word_count'], $result2['spam_word_count']);
        $this->assertEquals($result2['spam_word_count'], $result3['spam_word_count']);
    }
}

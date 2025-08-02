<?php

declare(strict_types=1);

namespace PHPMailService\Utils;

/**
 * Email Deliverability Checker
 *
 * Helps improve email deliverability by checking content and configuration
 */
class DeliverabilityChecker
{
    /**
     * Check if email content might trigger spam filters
     */
    public static function checkSpamRisk(string $subject, string $body): array
    {
        $issues = [];
        $risk = 'low';

        // Subject line checks
        $spamWords = [
            'free', 'winner', 'cash', 'urgent', 'limited time', 'act now',
            'click here', 'guaranteed', 'money back', 'risk free',
            'no obligation', 'special promotion', '100%', 'amazing',
            'incredible', 'miracle', 'breakthrough',
        ];

        $subjectLower = strtolower($subject);
        $spamWordCount = 0;

        foreach ($spamWords as $word) {
            if (strpos($subjectLower, $word) !== false) {
                $spamWordCount++;
                $issues[] = "Spam word detected in subject: '$word'";
            }
        }

        // Excessive caps check
        if (preg_match('/[A-Z]{3,}/', $subject)) {
            $issues[] = 'Excessive capitalization in subject';
            $risk = 'medium';
        }

        // Excessive exclamation marks
        if (substr_count($subject, '!') > 1) {
            $issues[] = 'Multiple exclamation marks in subject';
            $risk = 'medium';
        }

        // Body content checks
        $bodyLower = strtolower($body);

        // Check for excessive links
        $linkCount = preg_match_all('/https?:\/\//', $body);
        if ($linkCount > 3) {
            $issues[] = 'Too many links in email body';
            $risk = 'high';
        }

        // Check for spam phrases in body
        foreach ($spamWords as $word) {
            if (strpos($bodyLower, $word) !== false) {
                $spamWordCount++;
            }
        }

        if ($spamWordCount > 2) {
            $risk = 'high';
            $issues[] = "Multiple spam words detected ($spamWordCount words)";
        } elseif ($spamWordCount > 0) {
            $risk = 'medium';
        }

        return [
            'risk' => $risk,
            'issues' => $issues,
            'spam_word_count' => $spamWordCount,
            'recommendations' => self::getRecommendations($issues),
        ];
    }

    /**
     * Generate content recommendations for better deliverability
     */
    private static function getRecommendations(array $issues): array
    {
        $recommendations = [];

        if (empty($issues)) {
            $recommendations[] = 'Content looks good for deliverability';

            return $recommendations;
        }

        $recommendations[] = 'Use professional, business-appropriate language';
        $recommendations[] = 'Avoid excessive capitalization and punctuation';
        $recommendations[] = 'Limit promotional language and urgency words';
        $recommendations[] = 'Include your business address in email footer';
        $recommendations[] = 'Ensure recipients have opted in to receive emails';

        return $recommendations;
    }

    /**
     * Suggest better email content
     */
    public static function suggestImprovements(string $subject, string $body): array
    {
        $suggestions = [];

        // Subject improvements
        if (strlen($subject) > 50) {
            $suggestions[] = 'Shorten subject line (current: ' . strlen($subject) . ' chars, recommended: <50)';
        }

        if (strlen($subject) < 10) {
            $suggestions[] = 'Subject line is too short (current: ' . strlen($subject) . ' chars, recommended: 10-50)';
        }

        // Body improvements
        if (strlen($body) < 50) {
            $suggestions[] = 'Email body is very short, consider adding more content';
        }

        // Professional footer suggestion
        if (! preg_match('/unsubscribe|opt.?out/i', $body)) {
            $suggestions[] = 'Consider adding an unsubscribe link to the footer';
        }

        if (! preg_match('/address|street|city/i', $body)) {
            $suggestions[] = 'Consider adding your business address to the footer';
        }

        return $suggestions;
    }

    /**
     * Create a professional email template
     */
    public static function createProfessionalTemplate(
        string $content,
        string $companyName = 'Your Company Name',
        string $companyDomain = 'example.com',
        string $companyWebsite = 'https://example.com'
    ): string {
        return "
        <div style=\"font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; line-height: 1.6;\">
            <div style=\"background-color: #f8f9fa; padding: 20px; text-align: center; border-bottom: 3px solid #007bff;\">
                <h1 style=\"color: #333; margin: 0;\">{$companyName}</h1>
            </div>
            
            <div style=\"padding: 30px 20px;\">
                {$content}
            </div>
            
            <div style=\"background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666;\">
                <p>© " . date('Y') . " {$companyName}. All rights reserved.</p>
                <p>
                    You received this email because you are a valued customer.<br>
                    <a href=\"mailto:unsubscribe@{$companyDomain}?subject=Unsubscribe\" style=\"color: #007bff;\">Unsubscribe</a> | 
                    <a href=\"{$companyWebsite}\" style=\"color: #007bff;\">Visit our website</a>
                </p>
                <p style=\"margin-top: 10px;\">
                    {$companyName}<br>
                    Business Address (Update with your actual address)<br>
                    City, State, ZIP Code
                </p>
            </div>
        </div>";
    }
}

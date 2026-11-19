<?php

namespace Tests\Unit;

use App\Services\Gmail\GmailMessageBuilder;
use InvalidArgumentException;
use Tests\TestCase;

class GmailMessageBuilderTest extends TestCase
{
    private function decode(string $raw): string
    {
        return base64_decode(strtr($raw, '-_', '+/'));
    }

    public function test_builds_plain_text_message_with_unsubscribe_headers(): void
    {
        $m = (new GmailMessageBuilder())->build('Ayesha', 'ayesha@gmail.com', 'info@lead.example', 'Hello', "Line 1\nLine 2", 'https://app.example/u/1?signature=abc', 'ayesha@gmail.com');
        $mime = $this->decode($m['raw']);

        $this->assertStringNotContainsString('=', $m['raw'], 'raw must be unpadded base64url');
        $this->assertStringContainsString("From: Ayesha <ayesha@gmail.com>\r\n", $mime);
        $this->assertStringContainsString("To: info@lead.example\r\n", $mime);
        $this->assertStringContainsString("Content-Type: text/plain; charset=UTF-8\r\n", $mime);
        $this->assertStringContainsString("List-Unsubscribe: <mailto:ayesha@gmail.com?subject=unsubscribe>, <https://app.example/u/1?signature=abc>\r\n", $mime);
        $this->assertStringContainsString("List-Unsubscribe-Post: List-Unsubscribe=One-Click\r\n", $mime);
        $this->assertStringContainsString('Message-ID: ' . $m['message_id'], $mime);
        $this->assertMatchesRegularExpression('/^<[a-f0-9]+\.\d+@gmail\.com>$/', $m['message_id']);

        [, $body] = explode("\r\n\r\n", $mime, 2);
        $this->assertSame("Line 1\nLine 2", base64_decode(str_replace("\r\n", '', $body)));
    }

    public function test_non_ascii_subject_and_name_are_encoded(): void
    {
        $m = (new GmailMessageBuilder())->build('Zoë "Z" Khan', 'z@gmail.com', 'a@b.example', 'آپ کے کلینک کے لیے ایک خیال', 'x');
        $mime = $this->decode($m['raw']);

        $this->assertStringContainsString('Subject: =?UTF-8?B?' . base64_encode('آپ کے کلینک کے لیے ایک خیال') . '?=', $mime);
        $this->assertStringContainsString('From: =?UTF-8?B?' . base64_encode('Zoë "Z" Khan') . '?= <z@gmail.com>', $mime);
    }

    public function test_header_injection_is_rejected(): void
    {
        $b = new GmailMessageBuilder();
        $cases = [
            fn () => $b->build('A', 'a@gmail.com', 'b@x.example', "Hi\r\nBcc: victim@x.example", 'x'),
            fn () => $b->build("A\nBcc: v@x.example", 'a@gmail.com', 'b@x.example', 'Hi', 'x'),
            fn () => $b->build('A', 'a@gmail.com', "b@x.example\r\nBcc: v@x.example", 'Hi', 'x'),
            fn () => $b->build('A', 'a@gmail.com', 'b@x.example, c@x.example', 'Hi', 'x'),
            fn () => $b->build('A', 'a@gmail.com', 'not-an-email', 'Hi', 'x'),
        ];

        foreach ($cases as $i => $case) {
            try {
                $case();
                $this->fail("Case {$i} should have been rejected");
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_body_cannot_add_headers(): void
    {
        $m = (new GmailMessageBuilder())->build('A', 'a@gmail.com', 'b@x.example', 'Hi', "\r\n\r\nBcc: v@x.example\r\nhello");
        $mime = $this->decode($m['raw']);
        [$headers] = explode("\r\n\r\n", $mime, 2);

        $this->assertStringNotContainsString('Bcc', $headers);
        $this->assertStringNotContainsString('Bcc', explode("\r\n\r\n", $mime, 2)[1], 'Body is base64-encoded');
    }
}

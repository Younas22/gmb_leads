<?php

namespace App\Services\Gmail;

use InvalidArgumentException;

/**
 * Builds a plain-text RFC 5322 message for Gmail's users.messages.send
 * ("raw", base64url). Header values are validated so user or AI text cannot
 * inject extra headers or recipients.
 */
class GmailMessageBuilder
{
    /**
     * @return array{raw: string, message_id: string}
     */
    public function build(string $fromName, string $fromEmail, string $toEmail, string $subject, string $body, ?string $unsubscribeUrl = null, ?string $unsubscribeMailto = null, ?string $openPixelUrl = null): array
    {
        foreach ([$fromEmail, $toEmail] as $address) {
            if (!filter_var($address, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n,;<>"]/', $address)) {
                throw new InvalidArgumentException('Invalid email address.');
            }
        }
        if (preg_match('/[\r\n]/', $subject . $fromName)) {
            throw new InvalidArgumentException('Header values must be single-line.');
        }

        $domain = substr(strrchr($fromEmail, '@'), 1) ?: 'mail.local';
        $messageId = sprintf('<%s.%s@%s>', bin2hex(random_bytes(12)), time(), $domain);

        $headers = [
            'From' => $this->mailbox($fromName, $fromEmail),
            'To' => $toEmail,
            'Subject' => $this->encodeHeader($subject),
            'Date' => now()->toRfc2822String(),
            'Message-ID' => $messageId,
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Transfer-Encoding' => 'base64',
        ];
        // Open tracking: the same text as an HTML part with a 1x1 image (plain text stays for every client)
        $boundary = null;
        if ($openPixelUrl !== null) {
            $boundary = 'cnm_' . bin2hex(random_bytes(12));
            $headers['Content-Type'] = 'multipart/alternative; boundary="' . $boundary . '"';
            unset($headers['Content-Transfer-Encoding']);
        }

        $unsubscribe = array_filter([
            $unsubscribeMailto ? '<mailto:' . $unsubscribeMailto . '?subject=unsubscribe>' : null,
            $unsubscribeUrl ? '<' . $unsubscribeUrl . '>' : null,
        ]);
        if ($unsubscribe) {
            $headers['List-Unsubscribe'] = implode(', ', $unsubscribe);
        }
        if ($unsubscribeUrl) {
            $headers['List-Unsubscribe-Post'] = 'List-Unsubscribe=One-Click';
        }

        $lines = [];
        foreach ($headers as $name => $value) {
            $lines[] = $name . ': ' . $value;
        }

        $text = str_replace(["\r\n", "\r"], "\n", $body);
        $b64 = fn (string $v) => rtrim(chunk_split(base64_encode($v), 76, "\r\n"));

        if ($boundary === null) {
            $mime = implode("\r\n", $lines) . "\r\n\r\n" . $b64($text);
        } else {
            $mime = implode("\r\n", $lines) . "\r\n\r\n"
                . '--' . $boundary . "\r\n"
                . "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . $b64($text) . "\r\n"
                . '--' . $boundary . "\r\n"
                . "Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . $b64(self::html($text, $openPixelUrl)) . "\r\n"
                . '--' . $boundary . '--';
        }

        return [
            'raw' => rtrim(strtr(base64_encode($mime), '+/', '-_'), '='),
            'message_id' => $messageId,
        ];
    }

    /** The plain text as simple HTML: escaped, links clickable, line breaks kept, plus the open pixel. */
    public static function html(string $text, string $pixelUrl): string
    {
        $escaped = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $linked = preg_replace_callback('~https?://[^\s<>"\']+~i', function ($m) {
            $url = rtrim($m[0], '.,;:!?)');
            $rest = substr($m[0], strlen($url));

            return '<a href="' . $url . '">' . $url . '</a>' . $rest;
        }, $escaped);

        return '<!DOCTYPE html><html><body><div style="font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.5;color:#222">'
            . nl2br($linked, false)
            . '</div><img src="' . htmlspecialchars($pixelUrl, ENT_QUOTES, 'UTF-8') . '" width="1" height="1" alt="" style="display:block;border:0;width:1px;height:1px"></body></html>';
    }

    private function mailbox(string $name, string $email): string
    {
        $name = trim($name);
        if ($name === '') {
            return $email;
        }

        return $this->encodeHeader($name) . ' <' . $email . '>';
    }

    private function encodeHeader(string $value): string
    {
        return preg_match('/[^\x20-\x7E]/', $value) || preg_match('/["\\\\]/', $value)
            ? '=?UTF-8?B?' . base64_encode($value) . '?='
            : $value;
    }
}

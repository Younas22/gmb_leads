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
    public function build(string $fromName, string $fromEmail, string $toEmail, string $subject, string $body, ?string $unsubscribeUrl = null, ?string $unsubscribeMailto = null): array
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

        $mime = implode("\r\n", $lines) . "\r\n\r\n" . rtrim(chunk_split(base64_encode(str_replace(["\r\n", "\r"], "\n", $body)), 76, "\r\n"));

        return [
            'raw' => rtrim(strtr(base64_encode($mime), '+/', '-_'), '='),
            'message_id' => $messageId,
        ];
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

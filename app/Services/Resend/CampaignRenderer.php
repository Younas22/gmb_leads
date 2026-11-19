<?php

namespace App\Services\Resend;

use App\Models\MarketingContact;
use App\Models\UserSalesProfile;

/**
 * Turns a plain-text campaign into what Resend receives.
 *
 * Supported variables: {{first_name}}, {{last_name}} (with optional fallback,
 * e.g. {{first_name|there}}). For broadcasts they become Resend's contact
 * placeholders ({{{contact.first_name|there}}}); for previews and test sends they
 * are filled in locally. A footer with the sender's identity, postal address and
 * Resend's unsubscribe placeholder ({{{RESEND_UNSUBSCRIBE_URL}}}) is always added.
 */
class CampaignRenderer
{
    public const VARIABLES = ['first_name', 'last_name'];

    private const PATTERN = '/\{\{\s*(first_name|last_name)\s*(?:\|([^}]{0,40}))?\}\}/';

    /** Neutralise any triple-brace placeholders the user typed (only ours are allowed). */
    public static function sanitize(string $text): string
    {
        return str_replace(['{{{', '}}}'], ['{ {{', '}} }'], $text);
    }

    public function footer(?UserSalesProfile $profile, string $unsubscribeUrl): string
    {
        $sender = trim(implode(', ', array_filter([$profile?->sender_name, $profile?->company_name])));

        return "--\n" . ($sender !== '' ? $sender : 'Sender') . "\n" . ($profile?->sender_postal_address ?? '')
            . "\nYou receive this because you opted in. Unsubscribe: " . $unsubscribeUrl;
    }

    /** Text for a broadcast (variables become Resend placeholders). */
    public function broadcastText(string $body, ?UserSalesProfile $profile): string
    {
        $text = preg_replace_callback(self::PATTERN, fn ($m) => '{{{contact.' . $m[1] . '|' . trim($m[2] ?? '') . '}}}', self::sanitize($body));

        return rtrim($text) . "\n\n" . $this->footer($profile, '{{{RESEND_UNSUBSCRIBE_URL}}}');
    }

    public function broadcastSubject(string $subject): string
    {
        return preg_replace_callback(self::PATTERN, fn ($m) => '{{{contact.' . $m[1] . '|' . trim($m[2] ?? '') . '}}}', self::sanitize($subject));
    }

    /** Text filled in for one contact (preview, test send, local record). */
    public function personalize(string $text, ?MarketingContact $contact): string
    {
        return preg_replace_callback(self::PATTERN, function ($m) use ($contact) {
            $value = $contact?->{$m[1]};

            return filled($value) ? $value : trim($m[2] ?? '');
        }, self::sanitize($text));
    }

    /** Minimal, safe HTML version of plain text (escaped; links made clickable). */
    public function html(string $text): string
    {
        $escaped = htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Placeholders must survive escaping unchanged
        $escaped = preg_replace('/\{\{\{([A-Za-z_.]+(?:\|[^}]*)?)\}\}\}/', '{{{$1}}}', $escaped);
        $linked = preg_replace_callback('~(https?://[^\s<]+|\{\{\{RESEND_UNSUBSCRIBE_URL\}\}\})~', fn ($m) => '<a href="' . $m[1] . '">' . $m[1] . '</a>', $escaped);

        $paragraphs = array_map(fn ($p) => '<p>' . nl2br(trim($p), false) . '</p>', preg_split("/\n{2,}/", trim($linked)));

        return '<div style="font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.5;color:#111827">' . implode('', $paragraphs) . '</div>';
    }
}

<?php

namespace Tests\Feature\EmailTracking;

use App\Models\AiEmailDraft;
use App\Models\EmailTrackingSetting;
use App\Models\OutboundEmail;
use App\Models\User;
use Tests\Feature\EmailSending\GmailTestCase;

/**
 * Phase 4 helpers. Gmail is still fully faked (GmailTestCase): no request can
 * reach Google and no real email is sent.
 */
abstract class TrackingTestCase extends GmailTestCase
{
    public const BROWSER = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36';

    public const LINK_BODY = "Hi there,\n\nHere is a similar clinic site we built: https://pixelstudio.example/work/clinic?ref=1.\n"
        . "Our pricing (https://pixelstudio.example/pricing) is simple.\n\nAyesha";

    /** A sender whose plan has every Phase 4 feature. */
    protected function trackingSender(array $features = [], array $userAttrs = []): User
    {
        return $this->sender(array_merge([
            'email_tracking_enabled' => '1',
            'email_analytics_enabled' => '1',
            'email_followups_enabled' => '1',
        ], $features), $userAttrs);
    }

    protected function enableTracking(User $user, int $retention = 90): EmailTrackingSetting
    {
        $setting = EmailTrackingSetting::for($user);
        $setting->fill(['link_tracking_enabled' => true, 'retention_days' => $retention, 'disclosure_accepted_at' => now()])->save();

        return $setting;
    }

    /** Queue + run one send through the real controller and job (Gmail faked). */
    protected function sendDraft(User $user, AiEmailDraft $draft, ?bool $trackingShown = null, string $gmailId = '18c0ffee'): OutboundEmail
    {
        $this->gmailAccepts($gmailId, $gmailId);
        $payload = $this->sendPayload();
        if ($trackingShown !== null) {
            $payload['tracking_shown'] = $trackingShown ? '1' : '0';
        }

        $this->actingAs($user)->post("/user/email-sending/drafts/{$draft->id}/send", $payload)->assertSessionHasNoErrors();

        $email = OutboundEmail::where('draft_id', $draft->id)->latest('id')->firstOrFail();
        $this->runJob($email->id);

        return $email->fresh();
    }

    /** A sent email with tracked links, ready for click tests. */
    protected function trackedEmail(?User $user = null): array
    {
        $user ??= $this->trackingSender();
        $this->connect($user);
        $this->enableTracking($user);
        $draft = $this->draftFor($user, ['body' => self::LINK_BODY]);
        $email = $this->sendDraft($user, $draft, true);

        return [$user, $email, $this->tokensIn($email->sent_body)];
    }

    /** @return list<string> */
    protected function tokensIn(string $body): array
    {
        preg_match_all('~/r/([A-Za-z0-9_-]{32})~', $body, $m);

        return $m[1];
    }

    protected function click(string $token, string $agent = self::BROWSER, string $method = 'GET')
    {
        // Recipients are not signed in
        if (auth()->check()) {
            auth()->logout();
        }

        return $this->call($method, "/r/{$token}", [], [], [], ['HTTP_USER_AGENT' => $agent, 'REMOTE_ADDR' => '203.0.113.7']);
    }
}

<?php

namespace Tests\Feature\EmailSending;

use App\Models\AiEmailDraft;
use App\Models\ConnectedEmailAccount;
use App\Models\SavedLead;
use App\Models\User;
use App\Models\UserSalesProfile;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\Feature\AiOutreach\OutreachTestCase;

/**
 * Phase 3 helpers. Every Google endpoint (token, revoke, Gmail send) is faked;
 * a request to any other URL fails the test, so nothing can reach Google.
 */
abstract class GmailTestCase extends OutreachTestCase
{
    public const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    public const REVOKE_URL = 'https://oauth2.googleapis.com/revoke';
    public const SEND_URL = 'https://gmail.googleapis.com/gmail/v1/users/me/messages/send';

    /** @var list<HttpRequest> */
    public array $google = [];

    /** @var array<string, list<callable|\Illuminate\Http\Client\Response|\Throwable|array>> url => queued responses */
    public array $responses = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->responses = [];
        $this->google = [];
        $test = $this;

        Http::fake(function (HttpRequest $request) use ($test) {
            $url = strtok((string) $request->url(), '?');
            $test->google[] = $request;

            if (!array_key_exists($url, $test->responses)) {
                throw new \RuntimeException("Unexpected outbound HTTP request to {$url}");
            }
            $queue = &$test->responses[$url];
            $next = count($queue) > 1 ? array_shift($queue) : ($queue[0] ?? null);
            if ($next === null) {
                throw new \RuntimeException("No fake response left for {$url}");
            }
            if ($next instanceof \Throwable) {
                throw $next;
            }
            if (is_callable($next)) {
                return $next($request);
            }

            return Http::response($next[0], $next[1] ?? 200);
        });
    }

    /** Queue fake responses for a URL: each item is [json, status], a Throwable or a callable. */
    protected function respond(string $url, array ...$items): void
    {
        $this->responses[$url] = $items;
    }

    protected function respondWith(string $url, mixed ...$items): void
    {
        $this->responses[$url] = $items;
    }

    /** @return list<HttpRequest> */
    protected function requestsTo(string $url): array
    {
        return array_values(array_filter($this->google, fn (HttpRequest $r) => strtok((string) $r->url(), '?') === $url));
    }

    protected function idToken(array $claims = []): string
    {
        $claims = array_merge([
            'iss' => 'https://accounts.google.com',
            'aud' => config('gmail.client_id'),
            'sub' => 'google-sub-123',
            'email' => 'Ayesha@Gmail.com',
            'email_verified' => true,
            'exp' => time() + 3600,
            'iat' => time(),
        ], $claims);

        $b64 = fn (string $s) => rtrim(strtr(base64_encode($s), '+/', '-_'), '=');

        return $b64('{"alg":"RS256","typ":"JWT"}') . '.' . $b64(json_encode($claims)) . '.' . $b64('fake-signature');
    }

    protected function tokenResponse(array $overrides = [], array $claims = []): array
    {
        return [array_merge([
            'access_token' => 'ya29.access-token-1',
            'refresh_token' => '1//refresh-token-1',
            'expires_in' => 3599,
            'scope' => 'openid https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/gmail.send',
            'token_type' => 'Bearer',
            'id_token' => $this->idToken($claims),
        ], $overrides), 200];
    }

    /** A seller whose profile includes the sender identification needed for sending. */
    protected function sender(array $features = [], array $userAttrs = []): User
    {
        // Gmail sending is OFF by default; test plans enable it explicitly
        $user = $this->seller(array_merge(['gmail_sending_enabled' => '1'], $features), $userAttrs);
        UserSalesProfile::where('owner_user_id', $user->id)->update(['sender_postal_address' => '12 Mall Road, Lahore, Pakistan']);

        return $user;
    }

    protected function connect(User $user, array $attrs = []): ConnectedEmailAccount
    {
        return ConnectedEmailAccount::create(array_merge([
            'owner_user_id' => $user->getAccountOwner()->id,
            'user_id' => $user->id,
            'provider' => 'gmail',
            'email' => 'ayesha@gmail.com',
            'google_sub' => 'google-sub-123',
            'access_token' => 'ya29.valid-access',
            'refresh_token' => '1//refresh-valid',
            'token_expires_at' => now()->addHour(),
            'scopes' => ['openid', 'https://www.googleapis.com/auth/gmail.send'],
            'status' => ConnectedEmailAccount::STATUS_ACTIVE,
            'connected_at' => now(),
        ], $attrs));
    }

    protected function draftFor(User $owner, array $draftAttrs = [], array $leadAttrs = [], ?SavedLead $lead = null): AiEmailDraft
    {
        $lead ??= $this->leadFor($owner, array_merge(['email' => 'info@smiledental.example'], $leadAttrs));

        $draft = new AiEmailDraft();
        $draft->forceFill(array_merge([
            'owner_user_id' => $owner->id,
            'created_by_user_id' => $owner->id,
            'saved_lead_id' => $lead->id,
            'subject' => 'A quick idea for your clinic',
            'body' => "Hi there,\n\nI noticed your clinic focuses on family care. Would a short call help?\n\nAyesha",
            'generation_status' => 'generated',
            'review_status' => 'approved',
            'is_current' => true,
            'approved_at' => now(),
        ], $draftAttrs))->save();

        return $draft;
    }

    protected function gmailAccepts(string $id = '18c0ffee', string $thread = '18c0ffee'): void
    {
        $this->respond(self::SEND_URL, [['id' => $id, 'threadId' => $thread, 'labelIds' => ['SENT']], 200]);
    }

    protected function sendPayload(array $overrides = []): array
    {
        return array_merge([
            'recipient' => 'info@smiledental.example',
            'confirm' => '1',
            'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
        ], $overrides);
    }

    /** Run the send job right now (works even under Queue::fake()). */
    protected function runJob(int $outboundEmailId): void
    {
        app()->call([new \App\Jobs\SendGmailMessage($outboundEmailId), 'handle']);
    }

    /** Decoded MIME of a Gmail send request. */
    protected function mimeOf(HttpRequest $request): string
    {
        $raw = $request->data()['raw'];

        return base64_decode(strtr($raw, '-_', '+/'));
    }

    protected function decodedBody(string $mime): string
    {
        [, $body] = explode("\r\n\r\n", $mime, 2);

        return base64_decode(str_replace("\r\n", '', $body));
    }
}

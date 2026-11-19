<?php

namespace Tests\Feature\AiOutreach;

use App\Models\SavedLead;
use App\Models\User;
use App\Models\UserOffer;
use App\Models\UserSalesProfile;
use App\Services\Ai\AiRequest;
use App\Services\Ai\AiResponse;
use App\Services\Ai\Contracts\AiClient;
use App\Services\Web\HostResolver;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Security\SecurityTestCase;

/**
 * Helpers for Phase 2 tests: a scripted fake AI client (records every request,
 * never calls a real API), a fake DNS resolver and website fixtures.
 */
abstract class OutreachTestCase extends SecurityTestCase
{
    /** @var list<AiRequest> */
    public array $aiRequests = [];

    /** @var array<string, list<string>> host => IPs */
    public array $dns = [];

    /** @var array<string, array{0: string, 1: string}> url => [body, content-type] */
    public array $sites = [];

    protected function setUp(): void
    {
        parent::setUp();

        $test = $this;
        $this->app->instance(HostResolver::class, new class($test) implements HostResolver {
            public function __construct(private $test) {}
            public function resolve(string $host): array
            {
                return $this->test->dns[$host] ?? [];
            }
        });

        // AI is "configured" by default (fake, never a real API); tests override the responder.
        $this->fakeAi(fn () => '{}');
    }

    /**
     * @param callable(AiRequest, int): (string|\Throwable) $responder  returns JSON text or throws
     */
    protected function fakeAi(callable $responder): void
    {
        $test = $this;

        $this->app->instance(AiClient::class, new class($responder, $test) implements AiClient {
            private int $n = 0;
            public function __construct(private $responder, private $test) {}
            public function name(): string { return 'fake'; }
            public function model(): ?string { return 'fake-model'; }
            public function isConfigured(): bool { return true; }
            public function complete(AiRequest $request): AiResponse
            {
                $this->test->aiRequests[] = $request;
                $result = ($this->responder)($request, ++$this->n);
                if ($result instanceof \Throwable) {
                    throw $result;
                }
                return new AiResponse($result, 'fake-model', 'end_turn', 1000, 300);
            }
        });
    }

    /** A responder that writes valid analysis + draft JSON for the given offer. */
    protected function happyAi(int $offerId, array $draftOverrides = [], array $analysisOverrides = []): callable
    {
        return function (AiRequest $request) use ($offerId, $draftOverrides, $analysisOverrides) {
            if (self::isAnalysis($request)) {
                return json_encode(array_merge([
                    'business_summary' => 'A family dental clinic offering check-ups and whitening.',
                    'likely_needs' => [['need' => 'More online appointment requests', 'basis' => ['lead:category']]],
                    'recommended_offer_id' => $offerId,
                    'supporting_offer_ids' => [],
                    'match_reason' => 'The clinic relies on calls; an online booking site fits.',
                    'confidence' => 'medium',
                    'personalization_points' => [['point' => 'Family dental clinic', 'source' => 'lead:category']],
                ], $analysisOverrides));
            }

            return json_encode(array_merge([
                'subject' => 'A quick idea for your clinic',
                'greeting' => 'Hi there,',
                'body' => 'I came across your dental clinic and noticed how much you focus on family care. We build simple booking websites that let patients request appointments online, which can save your front desk time. Would a short 15-minute call next week be useful?',
                'call_to_action' => 'Short call next week',
                'used_sources' => ['lead:category'],
                'personalization_level' => 'specific',
            ], $draftOverrides));
        };
    }

    public static function isAnalysis(AiRequest $request): bool
    {
        return isset($request->jsonSchema['properties']['business_summary']);
    }

    protected function seller(array $features = [], array $userAttrs = []): User
    {
        $user = $this->makeSubscribedUser($userAttrs, array_merge([
            'ai_audits_monthly' => '50',
            'ai_email_drafts_monthly' => '50',
        ], $features));

        $this->profileFor($user);

        return $user;
    }

    protected function profileFor(User $owner): UserSalesProfile
    {
        $profile = new UserSalesProfile([
            'company_name' => 'Pixel Studio',
            'description' => 'We build booking websites for clinics.',
            'website' => 'https://pixelstudio.example',
            'language' => 'en',
            'tone' => 'friendly',
            'sender_name' => 'Ayesha',
            'signature' => "Ayesha Khan\nPixel Studio",
        ]);
        $profile->owner_user_id = $owner->id;
        $profile->save();

        return $profile;
    }

    protected function offerFor(User $owner, string $title = 'Online booking website', string $type = 'service', bool $active = true): UserOffer
    {
        $offer = new UserOffer(['type' => $type, 'title' => $title, 'description' => 'Fast site with online booking', 'is_active' => $active]);
        $offer->owner_user_id = $owner->id;
        $offer->save();

        return $offer;
    }

    protected function leadFor(User $owner, array $attrs = []): SavedLead
    {
        return $this->makeLead($owner, array_merge(['name' => 'Smile Dental', 'category' => 'dentist', 'website' => null], $attrs));
    }

    /** Fake a small public website at https://{host}/ */
    protected function fakeWebsite(string $host, string $homeHtml, array $extraPages = [], string $robots = "User-agent: *\nAllow: /"): void
    {
        $this->dns[$host] = ['93.184.216.34'];

        $this->sites["https://{$host}/robots.txt"] = [$robots, 'text/plain'];
        $this->sites["https://{$host}"] = [$homeHtml, 'text/html; charset=utf-8'];
        $this->sites["https://{$host}/"] = [$homeHtml, 'text/html; charset=utf-8'];
        foreach ($extraPages as $path => $html) {
            $this->sites["https://{$host}{$path}"] = [$html, 'text/html'];
        }

        // Fresh response objects per request; unknown URLs on faked hosts are 404
        $sites = &$this->sites;
        Http::fake(function ($request) use (&$sites) {
            $url = (string) $request->url();
            if (isset($sites[$url])) {
                return Http::response($sites[$url][0], 200, ['Content-Type' => $sites[$url][1]]);
            }

            return Http::response('Not found', 404, ['Content-Type' => 'text/html']);
        });
    }

    protected function sampleHtml(string $extraBody = ''): string
    {
        return <<<HTML
<!doctype html>
<html lang="en"><head>
<title>Smile Dental | Family Dentist</title>
<script>alert('should be stripped')</script>
<style>.x{color:red}</style>
</head>
<body>
<h1>Family dentistry in Lahore</h1>
<p>We offer check-ups, cleaning and whitening for the whole family. Call us to make an appointment with our friendly team today.</p>
<a href="/contact">Contact</a>
<a href="https://facebook.com/smiledental">Facebook</a>
{$extraBody}
</body></html>
HTML;
    }
}

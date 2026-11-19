<?php

namespace Tests\Feature\Security;

use App\Models\LeadFolder;
use App\Models\SavedLead;
use App\Services\LeadCenterImportService;
use App\Support\SafeUrl;
use PHPUnit\Framework\Attributes\DataProvider;

class XssAndUrlSafetyTest extends SecurityTestCase
{
    public static function unsafeUrls(): array
    {
        return [
            'javascript'          => ['javascript:alert(1)'],
            'javascript mixed'    => ['JaVaScRiPt:alert(document.cookie)'],
            'javascript slashes'  => ['javascript://example.com/%0Aalert(1)'],
            'data'                => ['data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg=='],
            'vbscript'            => ['vbscript:msgbox(1)'],
            'file'                => ['file:///etc/passwd'],
            'whitespace trick'    => ["java\tscript:alert(1)"],
            'empty'               => [''],
        ];
    }

    #[DataProvider('unsafeUrls')]
    public function test_safe_url_rejects_non_http_schemes(string $url): void
    {
        $this->assertNull(SafeUrl::http($url));
        $this->assertSame('#', SafeUrl::href($url));
    }

    public function test_safe_url_accepts_and_normalizes_http_urls(): void
    {
        $this->assertSame('https://example.com/a?b=1', SafeUrl::http('https://example.com/a?b=1'));
        $this->assertSame('http://example.com', SafeUrl::http('http://example.com'));
        $this->assertSame('https://example.com', SafeUrl::http('example.com'));
        $this->assertSame('https://example.com/x', SafeUrl::http('//example.com/x'));
    }

    public function test_leads_page_never_renders_javascript_urls_or_raw_markup(): void
    {
        $user = $this->makeSubscribedUser();
        $this->makeLead($user, [
            'name' => '<script>alert("name")</script>',
            'website' => 'javascript:alert(1)',
            'phone' => "1');alert('phone",
            'email' => "x@y.z');alert('mail",
        ]);

        $response = $this->actingAs($user)->get('/user/leads')->assertOk();

        $response->assertDontSee('href="javascript:', false);
        $response->assertDontSee('<script>alert("name")</script>', false);
        // Inline handlers use @js() JSON encoding, so a quote in the data cannot close the JS string
        $response->assertDontSee("copyToClipboard('1&#039;);alert", false);
        $jsonQuote = chr(92) . 'u0027'; // the JSON escape for a single quote
        $response->assertSee('1' . $jsonQuote . ');alert(' . $jsonQuote . 'phone', false);
    }

    public function test_layout_provides_escaping_helpers_used_by_lead_modal(): void
    {
        $user = $this->makeSubscribedUser();

        $this->actingAs($user)->get('/user/leads')
            ->assertOk()
            ->assertSee('window.cnmEscape = function', false)
            ->assertSee('window.cnmSafeUrl = function', false)
            ->assertSee('const lead = cnmEscapeDeep(rawLead);', false);
    }

    public function test_folder_names_are_not_injected_into_inline_handlers(): void
    {
        $user = $this->makeSubscribedUser();
        LeadFolder::forceCreate(['user_id' => $user->id, 'name' => "x');alert('folder", 'color' => 'blue']);

        $this->actingAs($user)->get('/user/leads')
            ->assertOk()
            ->assertDontSee("x');alert('folder", false);
    }

    public function test_admin_user_modal_escapes_user_supplied_fields(): void
    {
        $admin = $this->makeAdmin();
        $this->makeUser(['first_name' => '<img src=x onerror=alert(1)>', 'last_name' => 'Evil']);

        $this->actingAs($admin)->get('/admin/users')
            ->assertOk()
            ->assertDontSee('<img src=x onerror=alert(1)>', false)
            ->assertSee('const data = cnmEscapeDeep(rawData);', false);
    }

    public function test_extension_drops_unsafe_urls_but_keeps_the_response_contract(): void
    {
        $user = $this->makeSubscribedUser();
        $token = $this->postJson('/api/extension/login', ['email' => $user->email, 'password' => 'secret-password'])->json('token');
        $auth = ['Authorization' => 'Bearer ' . $token];
        $this->postJson('/api/extension/register-device', ['device_fingerprint' => 'fp-x'], $auth)->assertCreated();

        $this->postJson('/api/extension/save-leads', [
            'device_fingerprint' => 'fp-x',
            'search_data' => ['query' => 'dentist'],
            'leads' => [[
                'name' => 'Unsafe Url Dental',
                'website' => 'javascript:alert(1)',
                'profile' => 'data:text/html,hi',
                'social_links' => ['facebook' => 'https://facebook.com/ok', 'x' => 'javascript:alert(2)'],
            ]],
        ], $auth)
            ->assertOk()
            ->assertJsonStructure(['success', 'message', 'saved_count', 'duplicate_count', 'limit_skipped', 'daily_limit', 'used_today', 'remaining_today'])
            ->assertJsonPath('saved_count', 1);

        $lead = SavedLead::firstOrFail();
        $this->assertNull($lead->website);
        $this->assertNull($lead->google_profile_url);
        // The extension path stores social_links JSON-encoded inside the array cast (pre-existing behaviour)
        $social = $lead->social_links;
        if (is_string($social)) {
            $social = json_decode($social, true);
        }
        $this->assertSame(['facebook' => 'https://facebook.com/ok'], $social);
    }

    public function test_manual_social_link_edit_rejects_unsafe_scheme(): void
    {
        $user = $this->makeSubscribedUser();
        $lead = $this->makeLead($user);

        $this->actingAs($user)->postJson("/user/leads/{$lead->id}/contact", [
            'channel' => 'facebook',
            'value' => 'javascript:alert(1)',
        ])->assertStatus(422);

        $this->actingAs($user)->postJson("/user/leads/{$lead->id}/contact", [
            'channel' => 'facebook',
            'value' => 'facebook.com/real-page',
        ])->assertOk();

        $this->assertSame(['https://facebook.com/real-page'], $lead->fresh()->social_links);
    }

    public function test_flashed_error_messages_are_escaped(): void
    {
        $user = $this->makeSubscribedUser();

        $this->actingAs($user)
            ->withSession(['error' => 'Failed: <script>alert("flash")</script>'])
            ->get('/user/leads')
            ->assertOk()
            ->assertDontSee('<script>alert("flash")</script>', false)
            ->assertSee('&lt;script&gt;alert', false);
    }

    public function test_search_result_json_cannot_break_out_of_its_script_tag(): void
    {
        $view = file_get_contents(resource_path('views/user/search.blade.php'));

        $this->assertStringContainsString('json_encode($result, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)', $view);
        $this->assertStringNotContainsString('{!! json_encode($result) !!}', $view);

        // What that produces for a hostile business name
        $encoded = json_encode(['name' => '</script><script>alert(1)</script>'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $this->assertStringNotContainsString('</script>', $encoded);
    }

    public function test_lead_center_import_rejects_non_http_websites(): void
    {
        $user = $this->makeSubscribedUser();
        $result = (new LeadCenterImportService())->analyze($user->id, [
            ['company_name' => 'Bad Co', 'website' => 'javascript://example.com/%0Aalert(1)', 'email' => null],
            ['company_name' => 'Good Co', 'website' => 'good-co.example.com', 'email' => null],
        ]);

        $this->assertCount(1, $result['valid']);
        $this->assertSame('Good Co', $result['valid'][0]['company_name']);
    }
}

<?php

namespace Tests\Feature\AiOutreach;

use App\Models\AiLeadAnalysis;
use App\Models\LeadWebsiteAudit;
use App\Services\Ai\Outreach\EmailDraftWriter;
use App\Services\Ai\Outreach\LeadFacts;
use App\Services\Ai\SalesProfileContext;

/**
 * Quality evaluation matrix for the server-side draft gate (mocked: it checks
 * the rules applied to model output, not the real model's writing).
 */
class DraftQualityGateTest extends OutreachTestCase
{
    private const GOOD_BODY = 'I noticed Smile Dental focuses on family care in Lahore. Your homepage has no meta description, so search results show a random snippet. We build booking websites that fix small things like this and let patients request visits online. Would a short call next week help?';

    private function facts(bool $websiteVerified, string $offerDescription = 'Fast site with online booking'): array
    {
        $owner = $this->seller();
        $offer = $this->offerFor($owner);
        $offer->update(['description' => $offerDescription]);
        $lead = $this->leadFor($owner, ['website' => $websiteVerified ? 'https://smile.example' : null]);

        $analysis = AiLeadAnalysis::create([
            'owner_user_id' => $owner->id, 'saved_lead_id' => $lead->id, 'status' => 'analyzing', 'stage' => 'draft',
            'website_status' => $websiteVerified ? 'verified' : 'no_website', 'recommended_offer_id' => $offer->id,
        ]);
        $audit = null;
        if ($websiteVerified) {
            $audit = LeadWebsiteAudit::create([
                'owner_user_id' => $owner->id, 'saved_lead_id' => $lead->id, 'analysis_id' => $analysis->id,
                'status' => 'verified', 'url_requested' => 'https://smile.example', 'url_final' => 'https://smile.example/',
                'extracted_signals' => ['strengths' => []],
                'verified_findings' => [['id' => 'F1', 'type' => 'missing_meta_description', 'description' => 'No meta description', 'evidence' => 'x', 'source_url' => 'https://smile.example/', 'verification' => 'verified']],
            ]);
        }

        $facts = new LeadFacts($lead->fresh(), $analysis, $audit, $owner);

        return [$facts, app(SalesProfileContext::class)->forUser($owner)];
    }

    private function draft(array $overrides = []): array
    {
        return array_merge([
            'subject' => 'A small idea for Smile Dental',
            'greeting' => 'Hi there,',
            'body' => self::GOOD_BODY,
            'call_to_action' => 'Short call next week',
            'used_sources' => ['F1', 'lead:category'],
            'personalization_level' => 'specific',
        ], $overrides);
    }

    private function verdict(array $draft, bool $websiteVerified = true, string $offerDescription = 'Fast site with online booking'): string
    {
        [$facts, $context] = $this->facts($websiteVerified, $offerDescription);
        try {
            $clean = EmailDraftWriter::validate($draft, $facts, $context);
            return 'pass:' . $clean['personalization_level'];
        } catch (\InvalidArgumentException $e) {
            return 'reject';
        }
    }

    public function test_quality_matrix(): void
    {
        $matrix = [
            'evidence-backed specific draft'                  => [$this->draft(), true, 'pass:specific'],
            'claims website issue but website not checked'    => [$this->draft(['used_sources' => ['lead:category'], 'body' => 'Your website has no meta description, which hurts how Smile Dental appears in search. We build booking sites that solve this for clinics like yours. Would a short call next week help?']), false, 'reject'],
            'cites a finding that does not exist'             => [$this->draft(['used_sources' => ['F9']]), true, 'reject'],
            'ranking guarantee'                               => [$this->draft(['body' => self::GOOD_BODY . ' We guarantee first page of Google.']), true, 'reject'],
            'manipulative urgency'                            => [$this->draft(['body' => self::GOOD_BODY . ' Act now, this is a limited time only deal!']), true, 'reject'],
            'link to an unknown site'                         => [$this->draft(['body' => self::GOOD_BODY . ' See https://evil.example/offer']), true, 'reject'],
            'link to the sender\'s own site'                  => [$this->draft(['body' => self::GOOD_BODY . ' Examples: https://pixelstudio.example/work']), true, 'pass:specific'],
            'contains an email address'                       => [$this->draft(['body' => self::GOOD_BODY . ' Reply to me at someone@else.example']), true, 'reject'],
            'template placeholder left in'                    => [$this->draft(['greeting' => 'Hi [Name],']), true, 'reject'],
            'missing call to action'                          => [$this->draft(['call_to_action' => '']), true, 'reject'],
            'body too short to be useful'                     => [$this->draft(['body' => 'Want a website?']), true, 'reject'],
            'body far too long'                               => [$this->draft(['body' => str_repeat('This is long. ', 200)]), true, 'reject'],
            'multi-line subject'                              => [$this->draft(['subject' => "Hello\nthere"]), true, 'reject'],
            'claims "specific" with no evidence'              => [$this->draft(['used_sources' => []]), true, 'pass:generic'],
            'no-website lead, honest general draft'           => [$this->draft(['used_sources' => ['lead:category'], 'body' => 'I came across Smile Dental while looking at family clinics in Lahore. We build simple booking websites that let patients request appointments online. Would a short call next week be useful?']), false, 'pass:specific'],
        ];

        $results = [];
        foreach ($matrix as $case => [$draft, $verified, $expected]) {
            $results[$case] = $this->verdict($draft, $verified);
            $this->assertSame($expected, $results[$case], "Quality case failed: {$case}");
        }
    }

    public function test_guarantee_is_allowed_only_when_the_users_own_offer_says_it(): void
    {
        $draft = $this->draft(['body' => self::GOOD_BODY . ' It comes with our 30-day money-back guarantee.']);

        $this->assertSame('reject', $this->verdict($draft, true, 'Fast booking site'));
        $this->assertSame('pass:specific', $this->verdict($draft, true, 'Fast booking site with a 30-day money-back guarantee'));
    }

    public function test_signature_is_always_the_users_own(): void
    {
        $this->assertSame("Ayesha Khan\nPixel Studio", EmailDraftWriter::signature(['signature' => "Ayesha Khan\nPixel Studio"]));
        $this->assertSame("Best regards,\nAyesha\nPixel Studio", EmailDraftWriter::signature(['signature' => '', 'sender_name' => 'Ayesha', 'company_name' => 'Pixel Studio']));
        $this->assertSame('Best regards', EmailDraftWriter::signature([]));
    }

    public function test_prompt_carries_language_tone_and_offer(): void
    {
        $owner = $this->seller();
        \App\Models\UserSalesProfile::where('owner_user_id', $owner->id)->update(['language' => 'ur', 'tone' => 'formal']);
        $offer = $this->offerFor($owner, 'Booking website for clinics');
        $lead = $this->leadFor($owner);
        $this->fakeAi($this->happyAi($offer->id));

        $this->actingAs($owner)->postJson('/user/ai-outreach/analyze', ['lead_ids' => [$lead->id]])->assertOk();

        $draftRequest = $this->aiRequests[1];
        $this->assertStringContainsString('Write in Urdu, with a Formal tone', $draftRequest->system);
        $this->assertStringContainsString('Selected offer: Booking website for clinics', $draftRequest->messages[0]['content']);
        $this->assertStringContainsString('Do not write a signature', $draftRequest->system);
        $this->assertSame('ur', \App\Models\AiEmailDraft::sole()->language);
        $this->assertSame('formal', \App\Models\AiEmailDraft::sole()->tone);
    }
}

<?php

namespace Tests\Feature\Resend;

use App\Models\AiUsageLog;
use App\Models\UserSalesProfile;
use App\Services\Ai\AiRequest;
use App\Services\Ai\CampaignWriter;

/** Campaigns → Create: "Write with AI" fills subject + message from the AI Sales Profile. */
class CampaignAiWriteTest extends ResendTestCase
{
    public function test_ai_suggests_subject_and_message_from_the_profile_and_brief(): void
    {
        $user = $this->resendUser();
        $this->fakeAi(fn (AiRequest $r) => json_encode([
            'subject' => 'New: book your clinic visit online',
            'body' => "Hi {{first_name|there}},\n\nOur new booking page lets you pick a time in seconds.\n\nReply to see a demo.",
        ]));

        $this->actingAs($user)->get('/user/campaigns/create')->assertOk()->assertSee('Write with AI')->assertSee('Generate subject');

        $res = $this->actingAs($user)->postJson('/user/campaigns/ai-write', ['brief' => 'Announce online booking'])->assertOk();
        $res->assertJson(['ok' => true, 'subject' => 'New: book your clinic visit online']);
        $this->assertStringStartsWith('Hi {{first_name|there}},', $res->json('body'));

        $prompt = $this->aiRequests[0]->messages[0]['content'];
        $this->assertStringContainsString('Pixel Studio', $prompt, 'Uses the AI Sales Profile');
        $this->assertStringContainsString('Announce online booking', $prompt);
        $this->assertStringContainsString('Do NOT write a signature', $this->aiRequests[0]->system);
        $this->assertSame(1, AiUsageLog::where('feature', CampaignWriter::FEATURE)->where('status', 'success')->count(), 'Counts as one AI email draft');
        $this->assertSame(0, \App\Models\EmailCampaign::count(), 'Nothing is saved');
    }

    public function test_wrong_placeholders_or_footer_text_are_sent_back_to_the_model(): void
    {
        $user = $this->resendUser();
        $answers = [
            ['subject' => 'Hello [Name]', 'body' => "Hi [Name],\n\nNews.\n\nUnsubscribe here."],
            ['subject' => 'Hello', 'body' => "Hi {{first_name|there}},\n\nNews."],
        ];
        $this->fakeAi(function () use (&$answers) { return json_encode(array_shift($answers)); });

        $this->actingAs($user)->postJson('/user/campaigns/ai-write')->assertOk()->assertJson(['subject' => 'Hello']);
        $this->assertCount(2, $this->aiRequests, 'Invalid first answer was retried');
    }

    public function test_clear_messages_when_profile_is_empty_or_ai_is_off(): void
    {
        $user = $this->resendUser();
        UserSalesProfile::where('owner_user_id', $user->id)->update(['company_name' => null, 'description' => null]);
        $this->fakeAi(fn () => '{}');
        $this->actingAs($user)->postJson('/user/campaigns/ai-write')->assertStatus(422)->assertJsonFragment(['ok' => false])
            ->assertJsonPath('message', 'Fill in your AI Sales Profile (at least the business name and what you do) so AI can write from it.');

        $user2 = $this->resendUser([], ['email' => 'two@agency.example']);
        config(['ai.enabled' => false]);
        $this->actingAs($user2)->postJson('/user/campaigns/ai-write')->assertStatus(422)->assertJsonPath('message', 'AI features are not configured on this server yet.');
    }

    public function test_quota_is_respected(): void
    {
        $user = $this->resendUser(['ai_email_drafts_monthly' => '0']);
        $this->fakeAi(fn () => json_encode(['subject' => 'x', 'body' => 'Hi {{first_name|there}},' . "\n\nx"]));

        $this->actingAs($user)->postJson('/user/campaigns/ai-write')->assertStatus(422)->assertJsonPath('ok', false);
        $this->assertCount(0, $this->aiRequests);
    }
}

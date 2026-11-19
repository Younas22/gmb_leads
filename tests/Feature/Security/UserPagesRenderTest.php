<?php

namespace Tests\Feature\Security;

/**
 * The redesigned user pages render with the shared compact UI kit, and the dashboard's
 * AI Sales Assistant follows the plan (shown with real counts when included, hidden otherwise).
 */
class UserPagesRenderTest extends SecurityTestCase
{
    public function test_dashboard_shows_ai_assistant_when_the_plan_includes_ai(): void
    {
        $user = $this->makeSubscribedUser();

        $this->actingAs($user)->get('/user/dashboard')->assertOk()
            ->assertSee('data-testid="dashboard-ai"', false)
            ->assertSee('AI Sales Assistant')
            ->assertSee('Drafts to review')
            ->assertSee(route('user.ai-outreach.index'), false)
            ->assertSee(route('user.ai-profile.index'), false)
            ->assertDontSee('Videos included');
    }

    public function test_tutorials_page_renders_compact(): void
    {
        $user = $this->makeSubscribedUser();

        $this->actingAs($user)->get('/user/tutorials')->assertOk()
            ->assertSee('cnm-ui', false)
            ->assertSee('All Tutorials')
            ->assertSee('Your Progress')
            ->assertSee('data-tutorial=', false);
    }

    public function test_dashboard_hides_ai_assistant_when_ai_is_not_in_the_plan(): void
    {
        $user = $this->makeSubscribedUser([], ['ai_sales_profile_enabled' => 'false']);

        $this->actingAs($user)->get('/user/dashboard')->assertOk()
            ->assertDontSee('data-testid="dashboard-ai"', false)
            ->assertSee('Welcome back');
    }

    public function test_redesigned_pages_render_with_the_ui_kit(): void
    {
        $user = $this->makeSubscribedUser();

        foreach (['/user/dashboard', '/user/ai-outreach', '/user/campaigns', '/user/email-analytics',
                  '/user/email-follow-ups', '/user/subscription', '/user/feedback/history'] as $url) {
            $res = $this->actingAs($user)->get($url);
            if ($res->isRedirect()) {
                continue; // feature not in this test plan
            }
            $res->assertOk()->assertSee('cnm-ui', false);
        }
    }
}

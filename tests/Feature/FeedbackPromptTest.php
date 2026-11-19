<?php

namespace Tests\Feature;

use App\Models\UserFeedback;
use Tests\Feature\Security\SecurityTestCase;

/**
 * Post-login "Share Your Feedback" prompt: rating + required feedback + optional suggestion,
 * "used before?" question, and the rule that only users with no real activity may close it.
 */
class FeedbackPromptTest extends SecurityTestCase
{
    public function test_new_user_without_activity_gets_a_closable_prompt(): void
    {
        $user = $this->makeSubscribedUser();

        $this->actingAs($user)->get(route('user.dashboard'))
            ->assertOk()
            ->assertSee('const FB_PROMPT = {"has_activity":false}', false)
            ->assertSee('Have you used')
            ->assertDontSee('Leads saved');

        $this->actingAs($user)->postJson(route('user.feedback.dismiss-prompt'))
            ->assertOk()->assertJson(['success' => true]);

        $this->assertNotNull($user->fresh()->feedback_prompt_dismissed_at);

        $this->actingAs($user)->get(route('user.dashboard'))
            ->assertOk()
            ->assertSee('const FB_PROMPT = null', false);
    }

    public function test_user_with_activity_cannot_close_the_prompt_and_sees_their_activity(): void
    {
        $user = $this->makeSubscribedUser();
        $this->makeLead($user);
        $this->makeLead($user);

        $this->actingAs($user)->get(route('user.dashboard'))
            ->assertOk()
            ->assertSee('const FB_PROMPT = {"has_activity":true}', false)
            ->assertSee('Leads saved');

        // Answering "No" doesn't matter: the server refuses to dismiss.
        $this->actingAs($user)->postJson(route('user.feedback.dismiss-prompt'))
            ->assertStatus(422)
            ->assertJsonPath('activity.saved_leads', 2);

        $this->assertNull($user->fresh()->feedback_prompt_dismissed_at);
    }

    public function test_dismissed_new_user_is_asked_again_once_they_have_activity(): void
    {
        $user = $this->makeSubscribedUser();
        $this->actingAs($user)->postJson(route('user.feedback.dismiss-prompt'))->assertOk();

        $this->makeLead($user);

        $this->actingAs($user)->get(route('user.dashboard'))
            ->assertOk()
            ->assertSee('const FB_PROMPT = {"has_activity":true}', false);
    }

    public function test_feedback_is_required_and_suggestion_is_optional(): void
    {
        $user = $this->makeSubscribedUser();

        $this->actingAs($user)->postJson(route('user.feedback.store'), ['rating' => 4])
            ->assertStatus(422)->assertJsonValidationErrors(['message']);

        $this->actingAs($user)->postJson(route('user.feedback.store'), ['message' => 'Works well for me overall.'])
            ->assertStatus(422)->assertJsonValidationErrors(['rating']);

        $this->actingAs($user)->postJson(route('user.feedback.store'), [
            'rating' => 4,
            'message' => 'Works well for me overall.',
            'suggestion' => '',
            'used_before' => '0',
        ])->assertOk()->assertJson(['success' => true]);

        $feedback = UserFeedback::where('user_id', $user->id)->sole();
        $this->assertSame(4, (int) $feedback->rating);
        $this->assertSame('general', $feedback->feedback_type);
        $this->assertNull($feedback->suggestion);
        $this->assertFalse($feedback->used_before);
    }

    public function test_prompt_stops_once_feedback_is_given(): void
    {
        $user = $this->makeSubscribedUser();
        $this->makeLead($user);

        $this->actingAs($user)->postJson(route('user.feedback.store'), [
            'rating' => 5,
            'message' => 'Found good clients with it.',
            'suggestion' => 'Add more export columns.',
            'used_before' => '1',
        ])->assertOk();

        $this->assertSame('Add more export columns.', UserFeedback::where('user_id', $user->id)->value('suggestion'));

        $this->actingAs($user)->get(route('user.dashboard'))
            ->assertOk()
            ->assertSee('const FB_PROMPT = null', false);
    }

    public function test_admin_is_never_prompted(): void
    {
        $this->assertNull(\App\Services\FeedbackPrompt::state($this->makeAdmin()));
    }
}

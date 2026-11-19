<?php

namespace Tests\Feature;

use Tests\Feature\Security\SecurityTestCase;

/**
 * Login page: new two-column design keeps every auth hook (form ids, field names, CSRF,
 * routes), offers Google only when configured, and makes no unverified trust claims.
 */
class LoginPageTest extends SecurityTestCase
{
    public function test_login_page_renders_with_existing_form_hooks(): void
    {
        $this->get('/auth')->assertOk()
            ->assertSee('Welcome Back')
            ->assertSee('Find Verified')
            ->assertSee('id="loginFormElement"', false)
            ->assertSee('name="email" id="loginEmail"', false)
            ->assertSee('name="password" id="loginPassword"', false)
            ->assertSee('name="remember"', false)
            ->assertSee('name="_token"', false)
            ->assertSee(route('auth.login'), false)
            ->assertSee(route('auth.send.reset.link'), false)
            ->assertSee('id="signupFormElement"', false)
            ->assertSee(route('auth.signup'), false)
            ->assertSee('Secure Login')
            ->assertSee('No Credit Card Required')
            ->assertDontSee('GDPR')
            ->assertDontSee('256-bit');
    }

    public function test_google_button_only_when_google_login_is_configured(): void
    {
        config(['services.google.client_id' => null]);
        $this->get('/auth')->assertOk()->assertDontSee('Continue with Google');

        config(['services.google.client_id' => 'test-client-id']);
        $this->get('/auth')->assertOk()
            ->assertSee('Continue with Google')
            ->assertSee('href="' . route('auth.google') . '"', false);
    }

    public function test_password_toggles_are_accessible_buttons(): void
    {
        $html = $this->get('/auth')->assertOk()->getContent();
        $this->assertSame(3, substr_count($html, 'aria-label="Show password" aria-pressed="false"'));
    }

    public function test_homepage_sign_up_buttons_open_the_signup_form(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('href="' . route('auth.show') . '#signup"', false)
            ->assertSee('href="' . route('auth.show') . '"', false); // Login still opens sign-in
    }
}

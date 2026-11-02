<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Support\OrganizationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsOrganizationContext;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use BuildsOrganizationContext, RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_a_protected_route(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_root_sends_guests_to_login_and_members_to_organization_selection(): void
    {
        $this->get('/')->assertRedirect(route('login'));

        $user = $this->makeUser();
        $this->actingAs($user)->get('/')->assertRedirect(route('organizations.select'));
    }

    public function test_login_page_renders_in_the_english_fallback_without_raw_keys(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('lang="en"', false)
            ->assertSee('dir="ltr"', false)
            ->assertSee(__('ui.auth.login_title'))
            ->assertSee(__('ui.auth.invitation_only'))
            ->assertDontSee('ui.', false);
    }

    public function test_active_user_can_sign_in(): void
    {
        $user = $this->makeUser(['email' => 'member@example.test']);

        $response = $this->post(route('login.store'), [
            'email' => 'MEMBER@example.test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('organizations.select'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_are_rejected_and_session_is_not_authenticated(): void
    {
        $this->makeUser(['email' => 'member@example.test']);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => 'member@example.test',
                'password' => 'wrong-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_disabled_user_receives_a_safe_generic_error_and_cannot_sign_in(): void
    {
        $this->makeUser([
            'email' => 'disabled@example.test',
            'status' => UserStatus::Disabled,
        ]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => 'disabled@example.test',
                'password' => 'password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => __('auth.failed')]);

        $this->assertGuest();
    }

    public function test_authenticated_user_is_redirected_away_from_login(): void
    {
        $this->actingAs($this->makeUser())
            ->get(route('login'))
            ->assertRedirect(route('organizations.select'));
    }

    public function test_logout_invalidates_the_session_and_active_organization(): void
    {
        $organization = $this->makeOrganization();
        $user = $this->makeAdmin($organization);

        $this->withActiveOrganization($user, $organization)
            ->post(route('logout'))
            ->assertRedirect(route('login'))
            ->assertSessionMissing(OrganizationContext::SESSION_KEY);

        $this->assertGuest();
    }

    public function test_disabling_an_account_terminates_an_existing_session(): void
    {
        $organization = $this->makeOrganization();
        $user = $this->makeAdmin($organization);

        $this->withActiveOrganization($user, $organization)->get(route('dashboard'))->assertOk();

        $user->update(['status' => UserStatus::Disabled]);

        $this->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', __('ui.status.session_ended'));

        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        $this->makeUser(['email' => 'member@example.test']);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->from(route('login'))->post(route('login.store'), [
                'email' => 'member@example.test',
                'password' => 'wrong-password',
            ])->assertRedirect(route('login'));
        }

        $this->post(route('login.store'), [
            'email' => 'member@example.test',
            'password' => 'wrong-password',
        ])->assertTooManyRequests();

        $this->assertGuest();
    }
}

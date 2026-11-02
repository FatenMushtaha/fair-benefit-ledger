<?php

namespace Tests\Feature;

use App\Enums\MembershipRole;
use App\Support\OrganizationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOrganizationContext;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use BuildsOrganizationContext, RefreshDatabase;

    public function test_guest_falls_back_to_english_ltr(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('lang="en"', false)
            ->assertSee('dir="ltr"', false)
            ->assertDontSee('ui.', false)
            ->assertDontSee('validation.', false);
    }

    public function test_user_can_persist_arabic_and_pages_render_rtl(): void
    {
        $organization = $this->makeOrganization('Alpha Works');
        $user = $this->makeAdmin($organization);

        $this->actingAs($user)
            ->from(route('organizations.select'))
            ->post(route('locale.update'), ['locale' => 'ar'])
            ->assertRedirect(route('organizations.select'))
            ->assertSessionHas('status', __('ui.status.locale_updated'));

        $this->assertSame('ar', $user->fresh()->locale);

        $this->withActiveOrganization($user->fresh(), $organization)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('lang="ar"', false)
            ->assertSee('dir="rtl"', false)
            ->assertSee('لوحة التحكم')
            ->assertSee('<bdi dir="auto">', false)
            ->assertDontSee('ui.', false)
            ->assertDontSee('validation.', false);
    }

    public function test_user_can_switch_back_to_english_ltr(): void
    {
        $organization = $this->makeOrganization();
        $user = $this->makeAdmin($organization);
        $user->update(['locale' => 'ar']);

        $this->actingAs($user->fresh())
            ->from(route('organizations.select'))
            ->post(route('locale.update'), ['locale' => 'en'])
            ->assertRedirect(route('organizations.select'));

        $this->assertSame('en', $user->fresh()->locale);

        $this->actingAs($user->fresh())
            ->get(route('organizations.select'))
            ->assertOk()
            ->assertSee('lang="en"', false)
            ->assertSee('dir="ltr"', false);
    }

    public function test_invalid_locale_is_rejected_and_previous_value_remains(): void
    {
        $organization = $this->makeOrganization();
        $user = $this->makeAdmin($organization);
        $user->update(['locale' => 'ar']);

        $this->actingAs($user->fresh())
            ->from(route('organizations.select'))
            ->post(route('locale.update'), ['locale' => 'fr'])
            ->assertRedirect(route('organizations.select'))
            ->assertSessionHasErrors('locale');

        $this->assertSame('ar', $user->fresh()->locale);

        $this->actingAs($user->fresh())
            ->get(route('organizations.select'))
            ->assertSee('lang="ar"', false)
            ->assertSee('dir="rtl"', false);
    }

    public function test_locale_change_only_affects_the_authenticated_user(): void
    {
        $organization = $this->makeOrganization();
        $user = $this->makeAdmin($organization);
        $otherUser = $this->makeUser(['locale' => 'en']);

        $this->actingAs($user)
            ->post(route('locale.update'), ['locale' => 'ar'])
            ->assertRedirect();

        $this->assertSame('ar', $user->fresh()->locale);
        $this->assertSame('en', $otherUser->fresh()->locale);
    }

    public function test_invalid_legacy_locale_falls_back_to_english(): void
    {
        $organization = $this->makeOrganization();
        $user = $this->makeAdmin($organization);

        DB::table('users')->where('id', $user->id)->update(['locale' => 'fr']);

        $this->actingAs($user->fresh())
            ->get(route('organizations.select'))
            ->assertOk()
            ->assertSee('lang="en"', false)
            ->assertSee('dir="ltr"', false);
    }

    public function test_locale_change_does_not_change_the_active_organization(): void
    {
        $organization = $this->makeOrganization('Stable Organization');
        $user = $this->makeAdmin($organization);

        $this->withActiveOrganization($user, $organization)
            ->post(route('locale.update'), ['locale' => 'ar'])
            ->assertRedirect();

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Stable Organization')
            ->assertSee('lang="ar"', false);
        $this->assertSame($organization->id, (int) session(OrganizationContext::SESSION_KEY));
    }

    public function test_invitation_page_renders_without_raw_translation_keys(): void
    {
        $organization = $this->makeOrganization('Inviting Organization');
        $admin = $this->makeAdmin($organization);
        [, $token] = $this->makeInvitation($organization, $admin, 'invitee@example.test');

        $this->get($this->signedInvitationUrl($token))
            ->assertOk()
            ->assertSee('lang="en"', false)
            ->assertSee('dir="ltr"', false)
            ->assertSee('Inviting Organization')
            ->assertSee('invitee@example.test')
            ->assertSee(__('ui.auth.invitation_title'))
            ->assertDontSee('ui.', false)
            ->assertDontSee('validation.', false);
    }

    public function test_organization_selection_renders_in_arabic_without_raw_keys(): void
    {
        $organization = $this->makeOrganization('Alpha Works');
        $user = $this->makeUser(['locale' => 'ar']);
        $this->makeMembership($organization, $user, MembershipRole::Viewer);

        $this->actingAs($user)
            ->get(route('organizations.select'))
            ->assertOk()
            ->assertSee('lang="ar"', false)
            ->assertSee('dir="rtl"', false)
            ->assertSee('اختر مؤسسة')
            ->assertSee('Alpha Works')
            ->assertDontSee('ui.', false)
            ->assertDontSee('validation.', false);
    }
}

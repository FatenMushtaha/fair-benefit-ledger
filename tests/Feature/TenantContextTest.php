<?php

namespace Tests\Feature;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Models\OrganizationMembership;
use App\Services\OrganizationAccess;
use App\Services\OrganizationMembershipService;
use App\Support\OrganizationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\Concerns\BuildsOrganizationContext;
use Tests\TestCase;

class TenantContextTest extends TestCase
{
    use BuildsOrganizationContext, RefreshDatabase;

    public function test_member_can_switch_between_active_organizations(): void
    {
        $alpha = $this->makeOrganization('Alpha Works');
        $beta = $this->makeOrganization('Beta Works');
        $user = $this->makeUser();
        $this->makeMembership($alpha, $user, MembershipRole::Admin);
        $this->makeMembership($beta, $user, MembershipRole::Viewer);

        $this->actingAs($user)
            ->withSession([OrganizationContext::SESSION_KEY => $alpha->id])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Alpha Works')
            ->assertDontSee('Beta Works');

        $this->post(route('active-organization.store'), [
            'organization_id' => $beta->id,
        ])->assertRedirect(route('dashboard'));

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Beta Works')
            ->assertDontSee('Alpha Works');
    }

    public function test_guessed_or_non_member_organization_is_rejected_without_disclosure(): void
    {
        $alpha = $this->makeOrganization('Alpha Works');
        $beta = $this->makeOrganization('Beta Secret');
        $user = $this->makeUser();
        $this->makeMembership($alpha, $user, MembershipRole::Viewer);

        $this->actingAs($user)
            ->from(route('organizations.select'))
            ->post(route('active-organization.store'), [
                'organization_id' => $beta->id,
            ])
            ->assertRedirect(route('organizations.select'))
            ->assertSessionHasErrors('organization_id')
            ->assertSessionMissing(OrganizationContext::SESSION_KEY);

        $this->assertSame(
            __('ui.status.organization_unavailable'),
            session('errors')->first('organization_id'),
        );

        $this->get(route('dashboard'))->assertRedirect(route('organizations.select'));
    }

    public function test_malformed_organization_identifier_is_rejected(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->from(route('organizations.select'))
            ->post(route('active-organization.store'), [
                'organization_id' => 'not-an-identifier',
            ])
            ->assertRedirect(route('organizations.select'))
            ->assertSessionHasErrors('organization_id');

        $this->assertNull(session(OrganizationContext::SESSION_KEY));
    }

    public function test_tenant_route_requires_an_active_organization_selection(): void
    {
        $organization = $this->makeOrganization();
        $user = $this->makeAdmin($organization);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('organizations.select'))
            ->assertSessionHas('status', __('ui.status.select_organization_first'));
    }

    public function test_revoked_membership_invalidates_existing_session_context(): void
    {
        $organization = $this->makeOrganization('Revoked Organization');
        $user = $this->makeUser();
        $membership = $this->makeMembership($organization, $user, MembershipRole::Viewer);
        $admin = $this->makeAdmin($organization);

        $this->withActiveOrganization($user, $organization)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Revoked Organization');

        $this->inOrganization($organization, fn () => app(OrganizationMembershipService::class)->deactivate(
            $admin,
            $organization,
            (int) $membership->id,
            'Revoke membership.',
        ));

        $this->get(route('dashboard'))
            ->assertRedirect(route('organizations.select'))
            ->assertSessionMissing(OrganizationContext::SESSION_KEY)
            ->assertSessionHas('error', __('ui.status.organization_unavailable'));
    }

    public function test_cross_organization_membership_mutations_are_denied(): void
    {
        $alpha = $this->makeOrganization('Alpha Works');
        $beta = $this->makeOrganization('Beta Works');
        $alphaAdmin = $this->makeAdmin($alpha);
        $betaUser = $this->makeUser();
        $betaMembership = $this->makeMembership($beta, $betaUser, MembershipRole::Viewer);

        $this->withActiveOrganization($alphaAdmin, $alpha)
            ->patch(route('organizations.memberships.update', [$beta, $betaMembership]), [
                'role_key' => MembershipRole::Admin->value,
                'reason' => 'Cross-tenant escalation.',
            ])
            ->assertNotFound();

        $this->withActiveOrganization($alphaAdmin, $alpha)
            ->delete(route('organizations.memberships.destroy', [$beta, $betaMembership]), [
                'reason' => 'Cross-tenant removal.',
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('organization_user', [
            'id' => $betaMembership->id,
            'role_key' => MembershipRole::Viewer->value,
            'status' => MembershipStatus::Active->value,
        ]);
        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_tenant_record_creation_uses_context_and_ignores_requested_organization(): void
    {
        $alpha = $this->makeOrganization('Alpha Works');
        $beta = $this->makeOrganization('Beta Works');
        $user = $this->makeUser();

        $membership = $this->inOrganization($alpha, fn () => OrganizationMembership::create([
            'user_id' => $user->id,
            'organization_id' => $beta->id,
            'role_key' => MembershipRole::Viewer,
            'status' => MembershipStatus::Active,
        ]));

        $this->assertSame($alpha->id, (int) $membership->organization_id);
        $this->assertDatabaseHas('organization_user', [
            'id' => $membership->id,
            'organization_id' => $alpha->id,
        ]);
    }

    public function test_tenant_record_cannot_move_between_organizations(): void
    {
        $alpha = $this->makeOrganization('Alpha Works');
        $beta = $this->makeOrganization('Beta Works');
        $membership = $this->inOrganization($alpha, fn () => OrganizationMembership::factory()->create());

        $this->expectException(LogicException::class);

        $this->inOrganization($alpha, function () use ($membership, $beta): void {
            $membership->organization_id = $beta->id;
            $membership->save();
        });
    }

    public function test_tenant_queries_fail_closed_without_a_context(): void
    {
        $alpha = $this->makeOrganization('Alpha Works');
        $beta = $this->makeOrganization('Beta Works');
        $this->inOrganization($alpha, fn () => OrganizationMembership::factory()->create());

        app(OrganizationContext::class)->clear();
        $this->assertSame(0, OrganizationMembership::count());

        $this->inOrganization($beta, fn () => $this->assertSame(0, OrganizationMembership::count()));
        $this->inOrganization($alpha, fn () => $this->assertSame(1, OrganizationMembership::count()));
    }

    public function test_membership_directory_returns_only_the_callers_active_memberships(): void
    {
        $alpha = $this->makeOrganization('Alpha Works');
        $beta = $this->makeOrganization('Beta Works');
        $gamma = $this->makeOrganization('Gamma Works');
        $user = $this->makeUser();
        $this->makeMembership($alpha, $user);
        $this->makeMembership($beta, $user, MembershipRole::Viewer, MembershipStatus::Deactivated);
        $this->makeMembership($gamma, $this->makeUser());

        $memberships = app(OrganizationAccess::class)->activeMembershipsFor($user);

        $this->assertSame(['Alpha Works'], $memberships->pluck('organization.name')->all());
        $this->assertNull(app(OrganizationAccess::class)->activeMembershipFor($user, $beta->id));
        $this->assertNull(app(OrganizationAccess::class)->activeMembershipFor($user, $gamma->id));
    }

    public function test_deactivating_a_membership_revokes_the_users_server_side_sessions(): void
    {
        $organization = $this->makeOrganization();
        $admin = $this->makeAdmin($organization);
        $target = $this->makeUser();
        $membership = $this->makeMembership($organization, $target, MembershipRole::Viewer);

        DB::table('sessions')->insert([
            'id' => 'test-session-'.$target->id,
            'user_id' => $target->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => '',
            'last_activity' => time(),
        ]);

        $this->inOrganization($organization, fn () => app(OrganizationMembershipService::class)->deactivate(
            $admin,
            $organization,
            (int) $membership->id,
            'Revoke membership.',
        ));

        $this->assertDatabaseMissing('sessions', ['id' => 'test-session-'.$target->id]);
        $this->assertDatabaseHas('organization_user', [
            'id' => $membership->id,
            'status' => MembershipStatus::Deactivated->value,
        ]);
    }

    public function test_organization_selection_lists_only_current_memberships(): void
    {
        $alpha = $this->makeOrganization('Alpha Works');
        $beta = $this->makeOrganization('Beta Secret');
        $user = $this->makeUser();
        $this->makeMembership($alpha, $user);

        $this->actingAs($user)
            ->get(route('organizations.select'))
            ->assertOk()
            ->assertSee('Alpha Works')
            ->assertDontSee('Beta Secret');
    }

    public function test_user_without_memberships_sees_the_empty_state_and_no_organization_data(): void
    {
        $this->makeOrganization('Beta Secret');
        $user = $this->makeUser();

        $this->actingAs($user)
            ->get(route('organizations.select'))
            ->assertOk()
            ->assertSee(__('ui.organizations.empty_title'))
            ->assertSee(__('ui.organizations.empty_body'))
            ->assertDontSee('Beta Secret');
    }
}

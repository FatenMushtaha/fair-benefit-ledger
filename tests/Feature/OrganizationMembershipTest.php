<?php

namespace Tests\Feature;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Services\AuditRecorder;
use App\Services\OrganizationMembershipService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\Concerns\BuildsOrganizationContext;
use Tests\TestCase;

class OrganizationMembershipTest extends TestCase
{
    use BuildsOrganizationContext, RefreshDatabase;

    public function test_admin_can_change_a_membership_role_through_the_endpoint(): void
    {
        $organization = $this->makeOrganization();
        $admin = $this->makeAdmin($organization);
        $member = $this->makeUser();
        $membership = $this->makeMembership($organization, $member, MembershipRole::Viewer);

        $this->withActiveOrganization($admin, $organization)
            ->patch(route('organizations.memberships.update', [$organization, $membership]), [
                'role_key' => MembershipRole::Reviewer->value,
                'reason' => 'Promote to reviewer.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('organization_user', [
            'id' => $membership->id,
            'role_key' => MembershipRole::Reviewer->value,
        ]);
        $this->assertDatabaseHas('audit_events', [
            'organization_id' => $organization->id,
            'event_type' => 'organization.membership.role_changed',
            'actor_id' => $admin->id,
            'reason' => 'Promote to reviewer.',
        ]);
    }

    public function test_non_admin_cannot_change_a_membership_role(): void
    {
        $organization = $this->makeOrganization();
        $viewer = $this->makeUser();
        $this->makeMembership($organization, $viewer, MembershipRole::Viewer);
        $member = $this->makeUser();
        $membership = $this->makeMembership($organization, $member, MembershipRole::Viewer);

        $this->withActiveOrganization($viewer, $organization)
            ->patch(route('organizations.memberships.update', [$organization, $membership]), [
                'role_key' => MembershipRole::Admin->value,
                'reason' => 'Escalate.',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('organization_user', [
            'id' => $membership->id,
            'role_key' => MembershipRole::Viewer->value,
        ]);
        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_membership_endpoint_requires_a_reason(): void
    {
        $organization = $this->makeOrganization();
        $admin = $this->makeAdmin($organization);
        $membership = $this->makeMembership($organization, $this->makeUser(), MembershipRole::Viewer);

        $this->withActiveOrganization($admin, $organization)
            ->from(route('organizations.select'))
            ->patch(route('organizations.memberships.update', [$organization, $membership]), [
                'role_key' => MembershipRole::Admin->value,
            ])
            ->assertSessionHasErrors('reason');

        $this->assertDatabaseHas('organization_user', [
            'id' => $membership->id,
            'role_key' => MembershipRole::Viewer->value,
        ]);
    }

    public function test_admin_can_deactivate_a_membership_through_the_endpoint(): void
    {
        $organization = $this->makeOrganization();
        $admin = $this->makeAdmin($organization);
        $member = $this->makeUser();
        $membership = $this->makeMembership($organization, $member, MembershipRole::Viewer);

        $this->withActiveOrganization($admin, $organization)
            ->delete(route('organizations.memberships.destroy', [$organization, $membership]), [
                'reason' => 'Member left the program.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('organization_user', [
            'id' => $membership->id,
            'status' => MembershipStatus::Deactivated->value,
        ]);
        $this->assertDatabaseHas('audit_events', [
            'organization_id' => $organization->id,
            'event_type' => 'organization.membership.deactivated',
            'actor_id' => $admin->id,
        ]);
    }

    public function test_changing_to_the_same_role_does_not_append_an_audit_event(): void
    {
        $organization = $this->makeOrganization();
        $admin = $this->makeAdmin($organization);
        $membership = $this->makeMembership($organization, $this->makeUser(), MembershipRole::Viewer);

        $this->inOrganization($organization, fn () => app(OrganizationMembershipService::class)->changeRole(
            $admin,
            $organization,
            (int) $membership->id,
            MembershipRole::Viewer,
            'No-op role change.',
        ));

        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_deactivating_an_already_deactivated_membership_does_not_duplicate_audit(): void
    {
        $organization = $this->makeOrganization();
        $admin = $this->makeAdmin($organization);
        $membership = $this->makeMembership($organization, $this->makeUser(), MembershipRole::Viewer);

        $this->inOrganization($organization, fn () => app(OrganizationMembershipService::class)->deactivate(
            $admin,
            $organization,
            (int) $membership->id,
            'First deactivation.',
        ));
        $this->inOrganization($organization, fn () => app(OrganizationMembershipService::class)->deactivate(
            $admin,
            $organization,
            (int) $membership->id,
            'Second deactivation.',
        ));

        $this->assertDatabaseCount('audit_events', 1);
    }

    public function test_role_change_requires_the_active_organization_context(): void
    {
        $organization = $this->makeOrganization();
        $admin = $this->makeAdmin($organization);
        $membership = $this->makeMembership($organization, $this->makeUser(), MembershipRole::Viewer);

        $this->expectException(AuthorizationException::class);

        app(OrganizationMembershipService::class)->changeRole(
            $admin,
            $organization,
            (int) $membership->id,
            MembershipRole::Admin,
            'Missing active context.',
        );
    }

    public function test_role_change_rolls_back_when_the_audit_write_fails(): void
    {
        $organization = $this->makeOrganization();
        $admin = $this->makeAdmin($organization);
        $membership = $this->makeMembership($organization, $this->makeUser(), MembershipRole::Viewer);

        $this->mock(AuditRecorder::class, function ($mock): void {
            $mock->shouldReceive('record')->once()->andThrow(new RuntimeException('Audit unavailable.'));
        });

        try {
            $this->inOrganization($organization, fn () => app(OrganizationMembershipService::class)->changeRole(
                $admin,
                $organization,
                (int) $membership->id,
                MembershipRole::Admin,
                'Atomic role change.',
            ));
            $this->fail('The audit failure should have aborted the role change.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Audit unavailable.', $exception->getMessage());
        }

        $this->assertDatabaseHas('organization_user', [
            'id' => $membership->id,
            'role_key' => MembershipRole::Viewer->value,
        ]);
    }
}

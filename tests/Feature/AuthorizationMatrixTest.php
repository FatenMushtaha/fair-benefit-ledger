<?php

namespace Tests\Feature;

use App\Enums\MembershipRole;
use App\Enums\OrganizationCapability;
use App\Models\OrganizationMembership;
use App\Services\OrganizationAuthorizer;
use App\Services\OrganizationMembershipService;
use App\Support\OrganizationContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Tests\Concerns\BuildsOrganizationContext;
use Tests\TestCase;

class AuthorizationMatrixTest extends TestCase
{
    use BuildsOrganizationContext, RefreshDatabase;

    public function test_every_owner_approved_matrix_cell_is_enforced(): void
    {
        $organization = $this->makeOrganization();
        $authorizer = app(OrganizationAuthorizer::class);
        $checked = 0;

        foreach (MembershipRole::cases() as $role) {
            $user = $this->makeUser();
            $this->makeMembership($organization, $user, $role);

            foreach (OrganizationCapability::cases() as $capability) {
                $expected = in_array($capability, $role->capabilities(), true);

                $this->assertSame(
                    $expected,
                    $authorizer->allows($user, $organization, $capability),
                    "Unexpected {$capability->value} decision for {$role->value}.",
                );

                $checked++;
            }
        }

        $this->assertSame(
            count(MembershipRole::cases()) * count(OrganizationCapability::cases()),
            $checked,
        );
    }

    public function test_admin_can_manage_memberships_and_read_audit_logs(): void
    {
        $organization = $this->makeOrganization();
        $admin = $this->makeAdmin($organization);

        $this->inOrganization($organization, function () use ($admin, $organization): void {
            $this->assertTrue(Gate::forUser($admin)->allows('create', [OrganizationMembership::class, $organization]));
            $this->assertTrue(Gate::forUser($admin)->allows('viewAuditLogs', $organization));
        });
    }

    public function test_non_admin_roles_cannot_manage_memberships_or_read_audit_logs(): void
    {
        $organization = $this->makeOrganization();

        foreach ([MembershipRole::Reviewer, MembershipRole::DataEntry, MembershipRole::Viewer] as $role) {
            $user = $this->makeUser();
            $this->makeMembership($organization, $user, $role);

            $this->inOrganization($organization, function () use ($user, $organization): void {
                $this->assertFalse(Gate::forUser($user)->allows('create', [OrganizationMembership::class, $organization]));
                $this->assertFalse(Gate::forUser($user)->allows('viewAuditLogs', $organization));
            });
        }
    }

    public function test_unknown_role_is_denied_by_default(): void
    {
        $organization = $this->makeOrganization();
        $user = $this->makeUser();

        DB::table('organization_user')->insert([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role_key' => 'owner',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertFalse(
            app(OrganizationAuthorizer::class)->allows($user, $organization, OrganizationCapability::ManageOrganization),
        );
    }

    public function test_absent_active_organization_denies_policy_actions(): void
    {
        $organization = $this->makeOrganization();
        $admin = $this->makeAdmin($organization);

        app(OrganizationContext::class)->clear();

        $this->assertFalse(
            Gate::forUser($admin)->allows('create', [OrganizationMembership::class, $organization]),
        );
    }

    public function test_capabilities_do_not_cross_organization_boundaries(): void
    {
        $alpha = $this->makeOrganization('Alpha Works');
        $beta = $this->makeOrganization('Beta Works');
        $user = $this->makeUser();
        $this->makeMembership($alpha, $user, MembershipRole::Admin);
        $this->makeMembership($beta, $user, MembershipRole::Viewer);

        $authorizer = app(OrganizationAuthorizer::class);

        $this->assertTrue($authorizer->allows($user, $alpha, OrganizationCapability::ManageOrganization));
        $this->assertFalse($authorizer->allows($user, $beta, OrganizationCapability::ManageOrganization));
    }

    public function test_role_changes_take_effect_on_the_next_authorization_check(): void
    {
        $organization = $this->makeOrganization();
        $admin = $this->makeAdmin($organization);
        $target = $this->makeUser();
        $membership = $this->makeMembership($organization, $target, MembershipRole::Viewer);
        $authorizer = app(OrganizationAuthorizer::class);

        $this->assertFalse($authorizer->allows($target, $organization, OrganizationCapability::ManageOrganization));

        $this->inOrganization($organization, fn () => app(OrganizationMembershipService::class)->changeRole(
            $admin,
            $organization,
            (int) $membership->id,
            MembershipRole::Admin,
            'Promote to admin.',
        ));

        $this->assertTrue($authorizer->allows($target->fresh(), $organization, OrganizationCapability::ManageOrganization));
        $this->assertDatabaseHas('audit_events', [
            'organization_id' => $organization->id,
            'event_type' => 'organization.membership.role_changed',
            'subject_id' => (string) $membership->id,
        ]);
    }

    public function test_final_approval_requires_a_distinct_authorized_admin(): void
    {
        $organization = $this->makeOrganization();
        $reviewerAdmin = $this->makeAdmin($organization);
        $otherAdmin = $this->makeAdmin($organization);
        $reviewer = $this->makeUser();
        $this->makeMembership($organization, $reviewer, MembershipRole::Reviewer);
        $authorizer = app(OrganizationAuthorizer::class);

        $this->assertFalse($authorizer->canFinalApprove($reviewerAdmin, $organization, (int) $reviewerAdmin->id));
        $this->assertTrue($authorizer->canFinalApprove($otherAdmin, $organization, (int) $reviewerAdmin->id));
        $this->assertFalse($authorizer->canFinalApprove($reviewer, $organization, (int) $reviewerAdmin->id));
    }

    public function test_reviewers_cannot_final_approve(): void
    {
        $organization = $this->makeOrganization();
        $reviewer = $this->makeUser();
        $this->makeMembership($organization, $reviewer, MembershipRole::Reviewer);

        $this->expectException(AuthorizationException::class);

        app(OrganizationAuthorizer::class)->requireFinalApprove($reviewer, $organization, (int) $this->makeAdmin($organization)->id);
    }

    public function test_unknown_or_cross_tenant_membership_ids_do_not_grant_authorization(): void
    {
        $organization = $this->makeOrganization();
        $user = $this->makeUser();
        $unrelated = $this->makeUser();
        $this->makeMembership($organization, $user, MembershipRole::Admin);

        $authorizer = app(OrganizationAuthorizer::class);

        $this->assertFalse($authorizer->allows($unrelated, $organization, OrganizationCapability::ManageOrganization));
        $this->assertTrue($authorizer->allows($user, $organization, OrganizationCapability::FinalApproveAcceptance));
        $this->assertFalse($authorizer->allows($unrelated, $organization, OrganizationCapability::FinalApproveAcceptance));
    }
}

<?php

namespace App\Services;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Enums\OrganizationCapability;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrganizationMembershipService
{
    public function __construct(
        private readonly AuditRecorder $auditRecorder,
        private readonly OrganizationAuthorizer $authorizer,
        private readonly SessionInvalidator $sessionInvalidator,
    ) {}

    public function changeRole(User $actor, Organization $organization, int $membershipId, MembershipRole $role, string $reason): OrganizationMembership
    {
        $this->authorizer->requireInActiveOrganization($actor, $organization, OrganizationCapability::ManageOrganization);

        return DB::transaction(function () use ($actor, $organization, $membershipId, $role, $reason): OrganizationMembership {
            $membership = OrganizationMembership::query()->lockForUpdate()->findOrFail($membershipId);
            $oldRole = $membership->role_key;

            if ($oldRole === $role) {
                return $membership;
            }

            $membership->update(['role_key' => $role]);

            $this->auditRecorder->record(
                (int) $organization->id,
                (int) $actor->id,
                'organization.membership.role_changed',
                OrganizationMembership::class,
                $membership->id,
                $reason,
                ['from' => $oldRole->value, 'to' => $role->value],
            );

            return $membership->refresh();
        });
    }

    public function deactivate(User $actor, Organization $organization, int $membershipId, string $reason): OrganizationMembership
    {
        $this->authorizer->requireInActiveOrganization($actor, $organization, OrganizationCapability::ManageOrganization);

        $membership = DB::transaction(function () use ($actor, $organization, $membershipId, $reason): OrganizationMembership {
            $membership = OrganizationMembership::query()->lockForUpdate()->findOrFail($membershipId);

            if (! $membership->isActive()) {
                return $membership;
            }

            $membership->update(['status' => MembershipStatus::Deactivated]);

            $this->auditRecorder->record(
                (int) $organization->id,
                (int) $actor->id,
                'organization.membership.deactivated',
                OrganizationMembership::class,
                $membership->id,
                $reason,
            );

            return $membership->refresh();
        });

        $this->sessionInvalidator->forUser($membership->user);

        return $membership;
    }
}

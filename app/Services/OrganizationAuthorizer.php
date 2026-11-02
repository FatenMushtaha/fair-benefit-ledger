<?php

namespace App\Services;

use App\Enums\MembershipRole;
use App\Enums\OrganizationCapability;
use App\Models\Organization;
use App\Models\User;
use App\Support\OrganizationContext;
use Illuminate\Auth\Access\AuthorizationException;

class OrganizationAuthorizer
{
    public function __construct(
        private readonly OrganizationContext $organizationContext,
        private readonly OrganizationAccess $organizationAccess,
    ) {}

    public function allows(User $user, Organization $organization, OrganizationCapability $capability): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        $membership = $this->organizationAccess->activeMembershipFor($user, (int) $organization->getKey());

        if ($membership === null) {
            return false;
        }

        $role = MembershipRole::tryFrom((string) $membership->getRawOriginal('role_key'));

        return $role?->allows($capability) ?? false;
    }

    public function require(User $user, Organization $organization, OrganizationCapability $capability): void
    {
        if (! $this->allows($user, $organization, $capability)) {
            throw new AuthorizationException;
        }
    }

    public function isActiveOrganization(Organization $organization): bool
    {
        return $this->organizationContext->id() === (int) $organization->getKey();
    }

    public function requireActiveOrganization(Organization $organization): void
    {
        if (! $this->isActiveOrganization($organization)) {
            throw new AuthorizationException;
        }
    }

    public function requireInActiveOrganization(User $user, Organization $organization, OrganizationCapability $capability): void
    {
        $this->requireActiveOrganization($organization);
        $this->require($user, $organization, $capability);
    }

    public function canFinalApprove(User $user, Organization $organization, int $reviewActorId): bool
    {
        return $this->allows($user, $organization, OrganizationCapability::FinalApproveAcceptance)
            && (int) $user->getKey() !== $reviewActorId;
    }

    public function requireFinalApprove(User $user, Organization $organization, int $reviewActorId): void
    {
        if (! $this->canFinalApprove($user, $organization, $reviewActorId)) {
            throw new AuthorizationException;
        }
    }
}

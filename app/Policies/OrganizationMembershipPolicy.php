<?php

namespace App\Policies;

use App\Enums\OrganizationCapability;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use App\Services\OrganizationAuthorizer;

class OrganizationMembershipPolicy
{
    public function __construct(private readonly OrganizationAuthorizer $authorizer) {}

    public function viewAny(User $user, Organization $organization): bool
    {
        return $this->authorizer->isActiveOrganization($organization)
            && $this->authorizer->allows($user, $organization, OrganizationCapability::ManageOrganization);
    }

    public function view(User $user, OrganizationMembership $organizationMembership): bool
    {
        return $this->isManageable($user, $organizationMembership);
    }

    public function create(User $user, Organization $organization): bool
    {
        return $this->authorizer->isActiveOrganization($organization)
            && $this->authorizer->allows($user, $organization, OrganizationCapability::ManageOrganization);
    }

    public function update(User $user, OrganizationMembership $organizationMembership): bool
    {
        return $this->isManageable($user, $organizationMembership);
    }

    public function delete(User $user, OrganizationMembership $organizationMembership): bool
    {
        return $this->isManageable($user, $organizationMembership);
    }

    public function restore(User $user, OrganizationMembership $organizationMembership): bool
    {
        return false;
    }

    public function forceDelete(User $user, OrganizationMembership $organizationMembership): bool
    {
        return false;
    }

    private function isManageable(User $user, OrganizationMembership $organizationMembership): bool
    {
        $organization = $organizationMembership->organization;

        return $organization !== null
            && $this->authorizer->isActiveOrganization($organization)
            && (int) $organizationMembership->organization_id === (int) $organization->id
            && $this->authorizer->allows($user, $organization, OrganizationCapability::ManageOrganization);
    }
}

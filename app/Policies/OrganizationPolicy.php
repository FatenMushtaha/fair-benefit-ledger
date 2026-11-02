<?php

namespace App\Policies;

use App\Enums\OrganizationCapability;
use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationAuthorizer;

class OrganizationPolicy
{
    public function __construct(private readonly OrganizationAuthorizer $authorizer) {}

    public function manage(User $user, Organization $organization): bool
    {
        return $this->authorizer->isActiveOrganization($organization)
            && $this->authorizer->allows($user, $organization, OrganizationCapability::ManageOrganization);
    }

    public function viewAuditLogs(User $user, Organization $organization): bool
    {
        return $this->authorizer->isActiveOrganization($organization)
            && $this->authorizer->allows($user, $organization, OrganizationCapability::ReadAuditLogs);
    }
}

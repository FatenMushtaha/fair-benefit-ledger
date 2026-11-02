<?php

namespace App\Services;

use App\Enums\MembershipStatus;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Scopes\OrganizationScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Resolves the caller's own active memberships across organizations.
 *
 * This is the only intentional cross-tenant membership read in the foundation
 * layer. It is restricted to the authenticated user's own rows, returns no
 * other tenant data, and never accepts a caller-supplied organization id as
 * proof of membership.
 */
class OrganizationAccess
{
    /** @return Collection<int, OrganizationMembership> */
    public function activeMembershipsFor(User $user): Collection
    {
        if (! $user->isActive()) {
            return new Collection;
        }

        return OrganizationMembership::query()
            ->withoutGlobalScope(OrganizationScope::class)
            ->where('user_id', $user->id)
            ->where('status', MembershipStatus::Active->value)
            ->whereHas('organization')
            ->with('organization')
            ->get()
            ->sortBy(static fn (OrganizationMembership $membership): string => $membership->organization->name)
            ->values();
    }

    public function activeMembershipFor(User $user, int $organizationId): ?OrganizationMembership
    {
        if (! $user->isActive() || $organizationId < 1) {
            return null;
        }

        return OrganizationMembership::query()
            ->withoutGlobalScope(OrganizationScope::class)
            ->where('user_id', $user->id)
            ->where('organization_id', $organizationId)
            ->where('status', MembershipStatus::Active->value)
            ->whereHas('organization')
            ->with('organization')
            ->first();
    }

    public function hasActiveMembership(User $user, Organization $organization): bool
    {
        return $this->activeMembershipFor($user, (int) $organization->getKey()) !== null;
    }
}

<?php

namespace App\Actions;

use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationAccess;
use App\Support\OrganizationContext;
use Illuminate\Validation\ValidationException;

class SetActiveOrganization
{
    public function __construct(
        private readonly OrganizationAccess $organizationAccess,
        private readonly OrganizationContext $organizationContext,
    ) {}

    /**
     * @throws ValidationException
     */
    public function handle(User $user, int $organizationId): Organization
    {
        $membership = $this->organizationAccess->activeMembershipFor($user, $organizationId);

        if ($membership === null) {
            $this->organizationContext->clear();

            throw ValidationException::withMessages([
                'organization_id' => __('ui.status.organization_unavailable'),
            ]);
        }

        session([OrganizationContext::SESSION_KEY => (int) $membership->organization_id]);
        session()->regenerate();
        $this->organizationContext->set($membership->organization);

        return $membership->organization;
    }
}

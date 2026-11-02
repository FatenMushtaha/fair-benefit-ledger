<?php

namespace App\Http\Controllers;

use App\Enums\MembershipRole;
use App\Http\Requests\UpdateMembershipRequest;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Services\OrganizationMembershipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class OrganizationMembershipController extends Controller
{
    public function update(
        UpdateMembershipRequest $request,
        Organization $organization,
        OrganizationMembership $membership,
        OrganizationMembershipService $memberships,
    ): RedirectResponse {
        abort_unless((int) $membership->organization_id === (int) $organization->id, 404);
        Gate::authorize('update', $membership);

        $memberships->changeRole(
            $request->user(),
            $organization,
            (int) $membership->id,
            MembershipRole::from($request->validated('role_key')),
            $request->validated('reason'),
        );

        return back()->with('status', __('ui.status.membership_updated'));
    }

    public function destroy(
        UpdateMembershipRequest $request,
        Organization $organization,
        OrganizationMembership $membership,
        OrganizationMembershipService $memberships,
    ): RedirectResponse {
        abort_unless((int) $membership->organization_id === (int) $organization->id, 404);
        Gate::authorize('delete', $membership);

        $memberships->deactivate($request->user(), $organization, (int) $membership->id, $request->validated('reason'));

        return back()->with('status', __('ui.status.membership_deactivated'));
    }
}

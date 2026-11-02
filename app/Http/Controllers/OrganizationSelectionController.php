<?php

namespace App\Http\Controllers;

use App\Actions\SetActiveOrganization;
use App\Http\Requests\SetActiveOrganizationRequest;
use App\Services\OrganizationAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationSelectionController extends Controller
{
    public function index(Request $request, OrganizationAccess $organizationAccess): View
    {
        return view('organizations.select', [
            'memberships' => $organizationAccess->activeMembershipsFor($request->user()),
        ]);
    }

    public function store(SetActiveOrganizationRequest $request, SetActiveOrganization $setActiveOrganization): RedirectResponse
    {
        $organization = $setActiveOrganization->handle(
            $request->user(),
            (int) $request->validated('organization_id'),
        );

        return redirect()
            ->route('dashboard')
            ->with('status', __('ui.status.organization_selected', ['organization' => $organization->name]));
    }
}

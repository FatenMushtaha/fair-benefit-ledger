<?php

namespace App\Http\Controllers;

use App\Services\OrganizationAccess;
use App\Support\OrganizationContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, OrganizationAccess $organizationAccess, OrganizationContext $organizationContext): View
    {
        $membership = $organizationAccess->activeMembershipFor($request->user(), $organizationContext->requireId());

        abort_if($membership === null, 403);

        return view('dashboard', [
            'organization' => $membership->organization,
            'membership' => $membership,
        ]);
    }
}

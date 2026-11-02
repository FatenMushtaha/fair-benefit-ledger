<?php

namespace App\Http\Controllers;

use App\Enums\MembershipRole;
use App\Http\Requests\CreateMembershipInvitationRequest;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Services\OrganizationInvitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;

class OrganizationInvitationController extends Controller
{
    public function store(
        CreateMembershipInvitationRequest $request,
        Organization $organization,
        OrganizationInvitationService $invitations,
    ): JsonResponse {
        Gate::authorize('create', [OrganizationMembership::class, $organization]);

        $created = $invitations->invite(
            $request->user(),
            $organization,
            $request->validated('email'),
            MembershipRole::from($request->validated('role_key')),
            $request->validated('reason'),
        );

        return response()->json([
            'invitation_url' => URL::temporarySignedRoute(
                'invitations.show',
                $created['invitation']->expires_at,
                ['token' => $created['token']],
            ),
        ], 201);
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcceptInvitationRequest;
use App\Services\OrganizationInvitationService;
use App\Support\OrganizationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class InvitationAcceptanceController extends Controller
{
    public function show(Request $request, string $token, OrganizationInvitationService $invitations): View
    {
        $invitation = $invitations->findByToken($token);

        abort_if($invitation === null || ! $invitation->isUsable(), 404);

        return view('auth.accept-invitation', [
            'invitation' => $invitation,
            'token' => $token,
            'submitUrl' => URL::temporarySignedRoute(
                'invitations.accept',
                $invitation->expires_at,
                ['token' => $token],
            ),
        ]);
    }

    public function store(AcceptInvitationRequest $request, string $token, OrganizationInvitationService $invitations): RedirectResponse
    {
        $user = $invitations->accept($token, $request->validated());
        auth()->login($user);
        $request->session()->regenerate();
        $request->session()->forget(OrganizationContext::SESSION_KEY);

        return redirect()
            ->route('organizations.select')
            ->with('status', __('ui.status.invitation_accepted'));
    }
}

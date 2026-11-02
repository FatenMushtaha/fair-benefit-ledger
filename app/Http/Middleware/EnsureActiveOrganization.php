<?php

namespace App\Http\Middleware;

use App\Services\OrganizationAccess;
use App\Support\OrganizationContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveOrganization
{
    public function __construct(
        private readonly OrganizationAccess $organizationAccess,
        private readonly OrganizationContext $organizationContext,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        $storedOrganizationId = $request->session()->get(OrganizationContext::SESSION_KEY);

        if (! is_numeric($storedOrganizationId)) {
            $this->forgetContext($request);

            return redirect()
                ->route('organizations.select')
                ->with('status', __('ui.status.select_organization_first'));
        }

        $membership = $this->organizationAccess->activeMembershipFor($user, (int) $storedOrganizationId);

        if ($membership === null) {
            $this->forgetContext($request);

            return redirect()
                ->route('organizations.select')
                ->with('error', __('ui.status.organization_unavailable'));
        }

        $this->organizationContext->set($membership->organization);

        try {
            return $next($request);
        } finally {
            $this->organizationContext->clear();
        }
    }

    private function forgetContext(Request $request): void
    {
        $request->session()->forget(OrganizationContext::SESSION_KEY);
        $this->organizationContext->clear();
    }
}

<?php

namespace App\Providers;

use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Policies\OrganizationMembershipPolicy;
use App\Policies\OrganizationPolicy;
use App\Support\OrganizationContext;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(OrganizationContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Organization::class, OrganizationPolicy::class);
        Gate::policy(OrganizationMembership::class, OrganizationMembershipPolicy::class);

        RateLimiter::for('login', static fn (Request $request): Limit => Limit::perMinute(5)->by(
            Str::transliterate(Str::lower((string) $request->input('email')).'|'.$request->ip()),
        ));

        RedirectIfAuthenticated::redirectUsing(static fn (): string => route('organizations.select'));
    }
}

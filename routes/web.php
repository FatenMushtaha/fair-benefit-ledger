<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\InvitationAcceptanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\OrganizationInvitationController;
use App\Http\Controllers\OrganizationMembershipController;
use App\Http\Controllers\OrganizationSelectionController;
use Illuminate\Support\Facades\Route;

Route::get('/', static fn () => auth()->check()
    ? redirect()->route('organizations.select')
    : redirect()->route('login'));

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login.store');
});

Route::get('/invitations/{token}', [InvitationAcceptanceController::class, 'show'])
    ->middleware(['signed', 'throttle:20,1'])
    ->name('invitations.show');

Route::post('/invitations/{token}', [InvitationAcceptanceController::class, 'store'])
    ->middleware(['signed', 'throttle:20,1'])
    ->name('invitations.accept');

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/organizations/select', [OrganizationSelectionController::class, 'index'])->name('organizations.select');
    Route::post('/active-organization', [OrganizationSelectionController::class, 'store'])->name('active-organization.store');
    Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

    Route::middleware('org.context')->group(function (): void {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('/organizations/{organization}/invitations', [OrganizationInvitationController::class, 'store'])
            ->name('organizations.invitations.store');
        Route::patch('/organizations/{organization}/memberships/{membership}', [OrganizationMembershipController::class, 'update'])
            ->scopeBindings()
            ->name('organizations.memberships.update');
        Route::delete('/organizations/{organization}/memberships/{membership}', [OrganizationMembershipController::class, 'destroy'])
            ->scopeBindings()
            ->name('organizations.memberships.destroy');
    });
});

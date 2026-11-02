<?php

namespace Tests\Concerns;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Models\MembershipInvitation;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use App\Support\OrganizationContext;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

trait BuildsOrganizationContext
{
    protected function setUp(): void
    {
        parent::setUp();

        app(OrganizationContext::class)->clear();
    }

    protected function tearDown(): void
    {
        app(OrganizationContext::class)->clear();

        parent::tearDown();
    }

    protected function makeOrganization(string $name = 'Foundation Organization'): Organization
    {
        return Organization::factory()->create(['name' => $name]);
    }

    /** @param array<string, mixed> $attributes */
    protected function makeUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    protected function makeMembership(
        Organization $organization,
        User $user,
        MembershipRole $role = MembershipRole::Admin,
        MembershipStatus $status = MembershipStatus::Active,
    ): OrganizationMembership {
        return $this->inOrganization($organization, static fn (): OrganizationMembership => OrganizationMembership::factory()->create([
            'user_id' => $user->id,
            'role_key' => $role,
            'status' => $status,
        ]));
    }

    protected function makeAdmin(Organization $organization): User
    {
        $admin = $this->makeUser();
        $this->makeMembership($organization, $admin, MembershipRole::Admin);

        return $admin;
    }

    protected function inOrganization(Organization $organization, Closure $callback): mixed
    {
        return app(OrganizationContext::class)->run($organization, $callback);
    }

    protected function withActiveOrganization(User $user, Organization $organization): static
    {
        return $this->actingAs($user)->withSession([
            OrganizationContext::SESSION_KEY => (int) $organization->id,
        ]);
    }

    /** @return array{0: MembershipInvitation, 1: string} */
    protected function makeInvitation(
        Organization $organization,
        User $inviter,
        string $email,
        MembershipRole $role = MembershipRole::Viewer,
        ?CarbonInterface $expiresAt = null,
    ): array {
        $token = Str::random(64);

        $invitation = $this->inOrganization($organization, static fn (): MembershipInvitation => MembershipInvitation::factory()->create([
            'invited_by' => $inviter->id,
            'email' => mb_strtolower(trim($email)),
            'role_key' => $role,
            'token_hash' => hash('sha256', $token),
            'reason' => 'Test invitation reason.',
            'expires_at' => $expiresAt ?? now()->addDay(),
        ]));

        return [$invitation, $token];
    }

    protected function signedInvitationUrl(string $token, ?CarbonInterface $expiresAt = null): string
    {
        return URL::temporarySignedRoute(
            'invitations.accept',
            $expiresAt ?? now()->addHour(),
            ['token' => $token],
        );
    }
}

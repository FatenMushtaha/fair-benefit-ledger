<?php

namespace App\Services;

use App\Enums\InvitationStatus;
use App\Enums\Locale;
use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Enums\OrganizationCapability;
use App\Enums\UserStatus;
use App\Models\MembershipInvitation;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use App\Support\OrganizationContext;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrganizationInvitationService
{
    public function __construct(
        private readonly AuditRecorder $auditRecorder,
        private readonly OrganizationContext $organizationContext,
        private readonly OrganizationAuthorizer $authorizer,
    ) {}

    /** @return array{invitation: MembershipInvitation, token: string} */
    public function invite(
        User $actor,
        Organization $organization,
        string $email,
        MembershipRole $role,
        string $reason,
    ): array {
        $this->authorizer->requireInActiveOrganization($actor, $organization, OrganizationCapability::ManageOrganization);
        $normalizedEmail = mb_strtolower(trim($email));

        return DB::transaction(function () use ($actor, $organization, $normalizedEmail, $role, $reason): array {
            $alreadyMember = DB::table('organization_user')
                ->join('users', 'users.id', '=', 'organization_user.user_id')
                ->where('organization_user.organization_id', $organization->id)
                ->where('users.email', $normalizedEmail)
                ->exists();

            if ($alreadyMember) {
                throw ValidationException::withMessages(['email' => __('validation.custom.email.membership_exists')]);
            }

            $token = Str::random(64);
            $invitation = MembershipInvitation::create([
                'invited_by' => $actor->id,
                'email' => $normalizedEmail,
                'role_key' => $role,
                'token_hash' => hash('sha256', $token),
                'reason' => $reason,
                'status' => InvitationStatus::Pending,
                'expires_at' => now()->addHours((int) config('invitations.expire_hours')),
            ]);

            $this->auditRecorder->record(
                (int) $organization->id,
                (int) $actor->id,
                'organization.invitation.created',
                MembershipInvitation::class,
                $invitation->id,
                $reason,
                ['role_key' => $role->value],
            );

            return ['invitation' => $invitation, 'token' => $token];
        });
    }

    public function findByToken(string $token): ?MembershipInvitation
    {
        $tokenHash = hash('sha256', $token);
        $organizationId = DB::table('membership_invitations')
            ->where('token_hash', $tokenHash)
            ->value('organization_id');

        if ($organizationId === null) {
            return null;
        }

        $organization = Organization::find($organizationId);

        if ($organization === null) {
            return null;
        }

        return $this->organizationContext->run(
            $organization,
            static fn (): ?MembershipInvitation => MembershipInvitation::query()
                ->where('token_hash', $tokenHash)
                ->with('organization')
                ->first(),
        );
    }

    /** @param array{name?: string|null, email: string, password: string} $attributes */
    public function accept(string $token, array $attributes): User
    {
        $tokenHash = hash('sha256', $token);
        $organizationId = DB::table('membership_invitations')
            ->where('token_hash', $tokenHash)
            ->value('organization_id');

        if ($organizationId === null) {
            throw $this->invalidInvitation();
        }

        $organization = Organization::find($organizationId);

        if ($organization === null) {
            throw $this->invalidInvitation();
        }

        return $this->organizationContext->run($organization, function () use ($tokenHash, $attributes, $organization): User {
            return DB::transaction(function () use ($tokenHash, $attributes, $organization): User {
                $invitation = MembershipInvitation::query()
                    ->where('token_hash', $tokenHash)
                    ->lockForUpdate()
                    ->first();

                $normalizedEmail = mb_strtolower(trim($attributes['email']));

                if ($invitation === null
                    || ! $invitation->isUsable()
                    || ! hash_equals($invitation->email, $normalizedEmail)) {
                    throw $this->invalidInvitation();
                }

                $user = User::where('email', $invitation->email)->first();

                if ($user !== null) {
                    if (! $user->isActive() || ! Hash::check($attributes['password'], $user->password)) {
                        throw $this->invalidInvitation();
                    }
                } else {
                    $name = trim((string) ($attributes['name'] ?? ''));

                    if ($name === '') {
                        throw ValidationException::withMessages(['name' => __('validation.required', ['attribute' => __('ui.fields.name')])]);
                    }

                    $user = User::create([
                        'name' => $name,
                        'email' => $invitation->email,
                        'password' => $attributes['password'],
                        'locale' => Locale::English->value,
                        'status' => UserStatus::Active,
                    ]);
                }

                if (OrganizationMembership::query()->where('user_id', $user->id)->exists()) {
                    throw ValidationException::withMessages(['email' => __('validation.custom.email.membership_exists')]);
                }

                try {
                    $membership = OrganizationMembership::create([
                        'user_id' => $user->id,
                        'role_key' => $invitation->role_key,
                        'status' => MembershipStatus::Active,
                    ]);
                } catch (QueryException) {
                    throw ValidationException::withMessages(['email' => __('validation.custom.email.membership_exists')]);
                }

                $invitation->update([
                    'status' => InvitationStatus::Accepted,
                    'accepted_at' => now(),
                ]);

                $this->auditRecorder->record(
                    (int) $organization->id,
                    (int) $user->id,
                    'organization.membership.created',
                    OrganizationMembership::class,
                    $membership->id,
                    $invitation->reason,
                    ['role_key' => $membership->role_key->value],
                );

                return $user;
            });
        });
    }

    private function invalidInvitation(): ValidationException
    {
        return ValidationException::withMessages(['invitation' => __('auth.invitation_invalid')]);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\InvitationStatus;
use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Enums\UserStatus;
use App\Models\User;
use App\Services\AuditRecorder;
use App\Services\OrganizationInvitationService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Concerns\BuildsOrganizationContext;
use Tests\TestCase;

class OrganizationInvitationTest extends TestCase
{
    use BuildsOrganizationContext, RefreshDatabase;

    public function test_only_an_active_admin_can_create_an_invitation(): void
    {
        $organization = $this->makeOrganization();
        $admin = $this->makeAdmin($organization);

        $response = $this->withActiveOrganization($admin, $organization)->post(
            route('organizations.invitations.store', $organization),
            [
                'email' => 'invitee@example.test',
                'role_key' => MembershipRole::Reviewer->value,
                'reason' => 'Invite a reviewer.',
            ],
        );

        $response->assertCreated();
        $this->assertNotEmpty($response->json('invitation_url'));
        $this->assertDatabaseHas('membership_invitations', [
            'organization_id' => $organization->id,
            'email' => 'invitee@example.test',
            'role_key' => MembershipRole::Reviewer->value,
            'status' => InvitationStatus::Pending->value,
        ]);
        $this->assertDatabaseHas('audit_events', [
            'organization_id' => $organization->id,
            'actor_id' => $admin->id,
            'event_type' => 'organization.invitation.created',
        ]);
    }

    public function test_non_admin_member_cannot_create_an_invitation(): void
    {
        $organization = $this->makeOrganization();
        $viewer = $this->makeUser();
        $this->makeMembership($organization, $viewer, MembershipRole::Viewer);

        $this->withActiveOrganization($viewer, $organization)->post(
            route('organizations.invitations.store', $organization),
            [
                'email' => 'invitee@example.test',
                'role_key' => MembershipRole::Admin->value,
                'reason' => 'Escalation attempt.',
            ],
        )->assertForbidden();

        $this->assertDatabaseCount('membership_invitations', 0);
        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_admin_cannot_invite_into_an_organization_outside_the_active_context(): void
    {
        $activeOrganization = $this->makeOrganization('Active Organization');
        $otherOrganization = $this->makeOrganization('Other Organization');
        $admin = $this->makeAdmin($activeOrganization);

        $this->withActiveOrganization($admin, $activeOrganization)->post(
            route('organizations.invitations.store', $otherOrganization),
            [
                'email' => 'invitee@example.test',
                'role_key' => MembershipRole::Viewer->value,
                'reason' => 'Cross-tenant attempt.',
            ],
        )->assertForbidden();

        $this->assertDatabaseCount('membership_invitations', 0);
    }

    public function test_existing_membership_returns_a_validation_conflict(): void
    {
        $organization = $this->makeOrganization();
        $admin = $this->makeAdmin($organization);
        $member = $this->makeUser(['email' => 'member@example.test']);
        $this->makeMembership($organization, $member, MembershipRole::Viewer);

        $this->withActiveOrganization($admin, $organization)
            ->from(route('organizations.select'))
            ->post(route('organizations.invitations.store', $organization), [
                'email' => 'member@example.test',
                'role_key' => MembershipRole::Reviewer->value,
                'reason' => 'Duplicate membership attempt.',
            ])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('membership_invitations', 0);
    }

    public function test_new_invitee_acceptance_creates_only_the_invited_membership_and_role(): void
    {
        $organization = $this->makeOrganization();
        $admin = $this->makeAdmin($organization);

        $invitationUrl = $this->withActiveOrganization($admin, $organization)->post(
            route('organizations.invitations.store', $organization),
            [
                'email' => 'new.person@example.test',
                'role_key' => MembershipRole::DataEntry->value,
                'reason' => 'Add a data entry operator.',
            ],
        )->json('invitation_url');

        $token = basename((string) parse_url($invitationUrl, PHP_URL_PATH));

        $this->post($this->signedInvitationUrl($token), [
            'name' => 'New Person',
            'email' => 'new.person@example.test',
            'password' => 'CorrectHorseBattery',
            'password_confirmation' => 'CorrectHorseBattery',
        ])->assertRedirect(route('organizations.select'));

        $user = User::where('email', 'new.person@example.test')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('organization_user', [
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role_key' => MembershipRole::DataEntry->value,
            'status' => MembershipStatus::Active->value,
        ]);
        $this->assertSame(1, DB::table('organization_user')->where('user_id', $user->id)->count());
        $this->assertDatabaseHas('membership_invitations', [
            'organization_id' => $organization->id,
            'status' => InvitationStatus::Accepted->value,
        ]);
        $this->assertDatabaseHas('audit_events', [
            'organization_id' => $organization->id,
            'actor_id' => $user->id,
            'event_type' => 'organization.membership.created',
        ]);
    }

    public function test_existing_user_can_accept_a_second_organization_invitation(): void
    {
        $firstOrganization = $this->makeOrganization('First Organization');
        $secondOrganization = $this->makeOrganization('Second Organization');
        $secondAdmin = $this->makeAdmin($secondOrganization);

        $existingUser = $this->makeUser(['email' => 'existing@example.test']);
        $this->makeMembership($firstOrganization, $existingUser, MembershipRole::Viewer);

        [$invitation, $token] = $this->makeInvitation(
            $secondOrganization,
            $secondAdmin,
            'existing@example.test',
            MembershipRole::Reviewer,
        );

        $this->post($this->signedInvitationUrl($token), [
            'name' => '',
            'email' => 'existing@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('organizations.select'));

        $this->assertDatabaseHas('organization_user', [
            'organization_id' => $secondOrganization->id,
            'user_id' => $existingUser->id,
            'role_key' => MembershipRole::Reviewer->value,
        ]);
        $this->assertSame(2, DB::table('organization_user')->where('user_id', $existingUser->id)->count());
        $this->assertSame(InvitationStatus::Accepted, $invitation->fresh()->status);
    }

    public function test_wrong_password_does_not_create_a_membership(): void
    {
        $organization = $this->makeOrganization();
        $admin = $this->makeAdmin($organization);
        $existingUser = $this->makeUser(['email' => 'existing@example.test']);

        [$invitation, $token] = $this->makeInvitation($organization, $admin, 'existing@example.test');

        $this->from(route('organizations.select'))
            ->post($this->signedInvitationUrl($token), [
                'email' => 'existing@example.test',
                'password' => 'wrong-password',
                'password_confirmation' => 'wrong-password',
            ])
            ->assertSessionHasErrors('invitation');

        $this->assertDatabaseMissing('organization_user', [
            'organization_id' => $organization->id,
            'user_id' => $existingUser->id,
        ]);
        $this->assertSame(InvitationStatus::Pending, $invitation->fresh()->status);
    }

    public function test_expired_invitation_is_not_usable(): void
    {
        $organization = $this->makeOrganization();
        $admin = $this->makeAdmin($organization);
        [, $token] = $this->makeInvitation(
            $organization,
            $admin,
            'expired@example.test',
            MembershipRole::Viewer,
            now()->subMinute(),
        );

        $this->get($this->signedInvitationUrl($token))->assertNotFound();

        $beforeMemberships = DB::table('organization_user')->count();

        $this->from(route('organizations.select'))
            ->post($this->signedInvitationUrl($token), [
                'name' => 'Expired Person',
                'email' => 'expired@example.test',
                'password' => 'CorrectHorseBattery',
                'password_confirmation' => 'CorrectHorseBattery',
            ])
            ->assertSessionHasErrors('invitation');

        $this->assertDatabaseMissing('users', ['email' => 'expired@example.test']);
        $this->assertSame($beforeMemberships, DB::table('organization_user')->count());
    }

    public function test_revoked_invitation_is_not_usable(): void
    {
        $organization = $this->makeOrganization();
        $admin = $this->makeAdmin($organization);
        [$invitation, $token] = $this->makeInvitation($organization, $admin, 'revoked@example.test');

        $this->inOrganization($organization, fn () => $invitation->update([
            'status' => InvitationStatus::Revoked,
            'revoked_at' => now(),
        ]));

        $this->get($this->signedInvitationUrl($token))->assertNotFound();

        $this->from(route('organizations.select'))
            ->post($this->signedInvitationUrl($token), [
                'name' => 'Revoked Person',
                'email' => 'revoked@example.test',
                'password' => 'CorrectHorseBattery',
                'password_confirmation' => 'CorrectHorseBattery',
            ])
            ->assertSessionHasErrors('invitation');

        $this->assertDatabaseMissing('users', ['email' => 'revoked@example.test']);
    }

    public function test_guessed_token_creates_no_account_and_discloses_nothing(): void
    {
        $organization = $this->makeOrganization();
        $this->makeAdmin($organization);
        $token = str_repeat('a', 64);

        $beforeMemberships = DB::table('organization_user')->count();

        $this->get($this->signedInvitationUrl($token))->assertNotFound();

        $this->from(route('organizations.select'))
            ->post($this->signedInvitationUrl($token), [
                'name' => 'Guessed Person',
                'email' => 'guessed@example.test',
                'password' => 'CorrectHorseBattery',
                'password_confirmation' => 'CorrectHorseBattery',
            ])
            ->assertSessionHasErrors('invitation');

        $this->assertDatabaseMissing('users', ['email' => 'guessed@example.test']);
        $this->assertSame($beforeMemberships, DB::table('organization_user')->count());
    }

    public function test_invitation_email_must_match_the_invited_address(): void
    {
        $organization = $this->makeOrganization();
        $admin = $this->makeAdmin($organization);
        [, $token] = $this->makeInvitation($organization, $admin, 'invited@example.test');

        $beforeMemberships = DB::table('organization_user')->count();

        $this->from(route('organizations.select'))
            ->post($this->signedInvitationUrl($token), [
                'name' => 'Different Person',
                'email' => 'different@example.test',
                'password' => 'CorrectHorseBattery',
                'password_confirmation' => 'CorrectHorseBattery',
            ])
            ->assertSessionHasErrors('invitation');

        $this->assertDatabaseMissing('users', ['email' => 'different@example.test']);
        $this->assertSame($beforeMemberships, DB::table('organization_user')->count());
    }

    public function test_invitation_cannot_be_accepted_twice(): void
    {
        $organization = $this->makeOrganization();
        $admin = $this->makeAdmin($organization);
        [, $token] = $this->makeInvitation($organization, $admin, 'once@example.test');

        $payload = [
            'name' => 'Once Person',
            'email' => 'once@example.test',
            'password' => 'CorrectHorseBattery',
            'password_confirmation' => 'CorrectHorseBattery',
        ];

        $this->post($this->signedInvitationUrl($token), $payload)->assertRedirect(route('organizations.select'));

        $afterFirstMemberships = DB::table('organization_user')->count();

        $this->from(route('organizations.select'))
            ->post($this->signedInvitationUrl($token), $payload)
            ->assertSessionHasErrors('invitation');

        $this->assertDatabaseCount('users', 2);
        $this->assertSame($afterFirstMemberships, DB::table('organization_user')->count());
    }

    public function test_new_account_requires_a_name_and_the_strong_password_policy(): void
    {
        $organization = $this->makeOrganization();
        $admin = $this->makeAdmin($organization);
        [, $token] = $this->makeInvitation($organization, $admin, 'newname@example.test');

        $this->from(route('organizations.select'))
            ->post($this->signedInvitationUrl($token), [
                'name' => '',
                'email' => 'newname@example.test',
                'password' => 'short',
                'password_confirmation' => 'short',
            ])
            ->assertSessionHasErrors(['name', 'password']);

        $this->assertDatabaseMissing('users', ['email' => 'newname@example.test']);
    }

    public function test_disabled_existing_user_cannot_accept_an_invitation(): void
    {
        $organization = $this->makeOrganization();
        $admin = $this->makeAdmin($organization);
        $this->makeUser([
            'email' => 'disabled@example.test',
            'status' => UserStatus::Disabled,
        ]);

        [, $token] = $this->makeInvitation($organization, $admin, 'disabled@example.test');

        $beforeMemberships = DB::table('organization_user')->count();

        $this->from(route('organizations.select'))
            ->post($this->signedInvitationUrl($token), [
                'email' => 'disabled@example.test',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertSessionHasErrors('invitation');

        $this->assertSame($beforeMemberships, DB::table('organization_user')->count());
    }

    public function test_acceptance_rolls_back_when_the_audit_write_fails(): void
    {
        $organization = $this->makeOrganization();
        $admin = $this->makeAdmin($organization);
        [, $token] = $this->makeInvitation($organization, $admin, 'rollback@example.test');

        $beforeMemberships = DB::table('organization_user')->count();

        $this->mock(AuditRecorder::class, function ($mock): void {
            $mock->shouldReceive('record')->once()->andThrow(new RuntimeException('Audit unavailable.'));
        });

        try {
            app(OrganizationInvitationService::class)->accept($token, [
                'name' => 'Rollback Person',
                'email' => 'rollback@example.test',
                'password' => 'CorrectHorseBattery',
            ]);
            $this->fail('The audit failure should have aborted acceptance.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Audit unavailable.', $exception->getMessage());
        }

        $this->assertDatabaseMissing('users', ['email' => 'rollback@example.test']);
        $this->assertSame($beforeMemberships, DB::table('organization_user')->count());
        $this->assertDatabaseHas('membership_invitations', [
            'organization_id' => $organization->id,
            'email' => 'rollback@example.test',
            'status' => InvitationStatus::Pending->value,
        ]);
    }

    public function test_the_database_prevents_duplicate_memberships(): void
    {
        $organization = $this->makeOrganization();
        $user = $this->makeUser();
        $this->makeMembership($organization, $user);

        $this->expectException(QueryException::class);

        $this->makeMembership($organization, $user);
    }

    public function test_invitation_acceptance_creates_no_membership_for_a_guessed_organization(): void
    {
        $organization = $this->makeOrganization();
        $admin = $this->makeAdmin($organization);
        [, $token] = $this->makeInvitation($organization, $admin, 'guessed.org@example.test');

        $this->from(route('organizations.select'))
            ->post($this->signedInvitationUrl($token), [
                'name' => 'Guessed Org Person',
                'email' => 'guessed.org@example.test',
                'password' => 'CorrectHorseBattery',
                'password_confirmation' => 'CorrectHorseBattery',
                'organization_id' => 999999,
            ])
            ->assertRedirect(route('organizations.select'));

        $user = User::where('email', 'guessed.org@example.test')->firstOrFail();
        $this->assertDatabaseHas('organization_user', [
            'organization_id' => $organization->id,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseMissing('organization_user', [
            'organization_id' => 999999,
            'user_id' => $user->id,
        ]);
    }
}

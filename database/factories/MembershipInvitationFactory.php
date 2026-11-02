<?php

namespace Database\Factories;

use App\Enums\InvitationStatus;
use App\Enums\MembershipRole;
use App\Models\MembershipInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MembershipInvitation>
 */
class MembershipInvitationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invited_by' => User::factory(),
            'email' => fake()->unique()->safeEmail(),
            'role_key' => MembershipRole::Viewer,
            'token_hash' => hash('sha256', fake()->unique()->uuid()),
            'reason' => fake()->sentence(),
            'status' => InvitationStatus::Pending,
            'expires_at' => now()->addDays(3),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (): array => ['expires_at' => now()->subMinute()]);
    }

    public function revoked(): static
    {
        return $this->state(fn (): array => [
            'status' => InvitationStatus::Revoked,
            'revoked_at' => now(),
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn (): array => [
            'status' => InvitationStatus::Accepted,
            'accepted_at' => now(),
        ]);
    }
}

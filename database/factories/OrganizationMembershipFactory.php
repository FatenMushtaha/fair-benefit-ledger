<?php

namespace Database\Factories;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Enums\UserStatus;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrganizationMembership>
 */
class OrganizationMembershipFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * The organization id is assigned by the tenant context, never by the
     * factory, so callers must run this inside a validated context.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'role_key' => MembershipRole::Viewer,
            'status' => MembershipStatus::Active,
        ];
    }

    public function role(MembershipRole $role): static
    {
        return $this->state(fn (): array => ['role_key' => $role]);
    }

    public function deactivated(): static
    {
        return $this->state(fn (): array => ['status' => MembershipStatus::Deactivated]);
    }

    public function forDisabledUser(): static
    {
        return $this->state(fn (): array => [
            'user_id' => User::factory()->state(['status' => UserStatus::Disabled]),
        ]);
    }
}

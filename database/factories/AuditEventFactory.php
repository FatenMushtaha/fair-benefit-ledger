<?php

namespace Database\Factories;

use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AuditEvent>
 */
class AuditEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'actor_id' => User::factory(),
            'event_type' => 'test.event',
            'subject_type' => 'TestSubject',
            'subject_id' => (string) fake()->randomNumber(),
            'reason' => fake()->sentence(),
            'payload' => [],
            'occurred_at' => now(),
            'correlation_id' => Str::uuid(),
        ];
    }
}

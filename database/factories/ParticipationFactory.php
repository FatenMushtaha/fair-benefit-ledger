<?php

namespace Database\Factories;

use App\Enums\ParticipationStatus;
use App\Models\Participation;
use Illuminate\Database\Eloquent\Factories\Factory;

class ParticipationFactory extends Factory
{
    protected $model = Participation::class;

    public function definition(): array
    {
        return [
            'person_id' => null,
            'program_id' => null,
            'status' => ParticipationStatus::Active,
            'start_date' => fake()->date(),
            'end_date' => null,
        ];
    }
}

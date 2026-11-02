<?php

namespace Database\Factories;

use App\Enums\CycleStatus;
use App\Models\Cycle;
use Illuminate\Database\Eloquent\Factories\Factory;

class CycleFactory extends Factory
{
    protected $model = Cycle::class;

    public function definition(): array
    {
        return [
            'program_id' => null,
            'rule_version_id' => null,
            'name' => 'Cycle '.fake()->word(),
            'status' => CycleStatus::Draft,
            'start_date' => fake()->date(),
            'available_quantity' => 10,
            'automation_mode' => 'manual',
        ];
    }
}

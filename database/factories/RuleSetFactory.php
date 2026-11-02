<?php

namespace Database\Factories;

use App\Enums\RuleSetStatus;
use App\Models\RuleSet;
use Illuminate\Database\Eloquent\Factories\Factory;

class RuleSetFactory extends Factory
{
    protected $model = RuleSet::class;

    public function definition(): array
    {
        return [
            'name' => 'Rules '.fake()->word(),
            'purpose' => 'eligibility',
            'status' => RuleSetStatus::Active,
            'current_version_id' => null,
        ];
    }
}

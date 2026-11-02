<?php

namespace Database\Factories;

use App\Models\PersonName;
use Illuminate\Database\Eloquent\Factories\Factory;

class PersonNameFactory extends Factory
{
    protected $model = PersonName::class;

    public function definition(): array
    {
        return [
            'person_id' => null,
            'name' => fake()->name(),
            'normalized_name' => fake()->slug(2),
            'script' => 'latin',
            'source' => 'import',
        ];
    }
}

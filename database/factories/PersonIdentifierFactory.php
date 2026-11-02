<?php

namespace Database\Factories;

use App\Models\PersonIdentifier;
use Illuminate\Database\Eloquent\Factories\Factory;

class PersonIdentifierFactory extends Factory
{
    protected $model = PersonIdentifier::class;

    public function definition(): array
    {
        return [
            'person_id' => null,
            'type' => 'national_id',
            'normalized_value' => fake()->numerify('##########'),
            'authoritative_key' => null,
            'source' => 'import',
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

class PersonFactory extends Factory
{
    protected $model = Person::class;

    public function definition(): array
    {
        return [
            'display_name' => fake()->name(),
            'normalized_name' => fake()->slug(2),
            'date_of_birth' => fake()->date(),
            'phone' => fake()->phoneNumber(),
        ];
    }
}

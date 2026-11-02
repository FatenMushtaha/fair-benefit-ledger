<?php

namespace Database\Factories;

use App\Models\RuleVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

class RuleVersionFactory extends Factory
{
    protected $model = RuleVersion::class;

    public function definition(): array
    {
        return [
            'rule_set_id' => null,
            'version' => 1,
            'config' => ['version' => 1],
            'created_by' => null,
            'created_at' => now(),
        ];
    }
}

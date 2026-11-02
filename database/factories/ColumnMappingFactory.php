<?php

namespace Database\Factories;

use App\Models\ColumnMapping;
use Illuminate\Database\Eloquent\Factories\Factory;

class ColumnMappingFactory extends Factory
{
    protected $model = ColumnMapping::class;

    public function definition(): array
    {
        return [
            'owner_id' => null,
            'source_headers' => ['Name'],
            'canonical_map' => ['Name' => 'name'],
            'locale_hints' => null,
            'approved' => false,
        ];
    }
}

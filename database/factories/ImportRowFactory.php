<?php

namespace Database\Factories;

use App\Enums\ImportRowStatus;
use App\Models\ImportRow;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImportRowFactory extends Factory
{
    protected $model = ImportRow::class;

    public function definition(): array
    {
        return [
            'import_batch_id' => null,
            'row_number' => 2,
            'raw_payload' => ['name' => 'Test'],
            'status' => ImportRowStatus::Staged,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Enums\ImportBatchPurpose;
use App\Enums\ImportBatchStatus;
use App\Models\ImportBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImportBatchFactory extends Factory
{
    protected $model = ImportBatch::class;

    public function definition(): array
    {
        return [
            'purpose' => ImportBatchPurpose::CycleApplicants,
            'private_path' => 'imports/1/test.csv',
            'format' => 'csv',
            'status' => ImportBatchStatus::Uploaded,
        ];
    }
}

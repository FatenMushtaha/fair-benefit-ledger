<?php

namespace Database\Factories;

use App\Enums\CycleApplicantStatus;
use App\Models\CycleApplicant;
use Illuminate\Database\Eloquent\Factories\Factory;

class CycleApplicantFactory extends Factory
{
    protected $model = CycleApplicant::class;

    public function definition(): array
    {
        return [
            'cycle_id' => null,
            'import_row_id' => null,
            'person_id' => null,
            'status' => CycleApplicantStatus::Pending,
            'verified_data_flags' => null,
            'registered_at' => now(),
        ];
    }
}

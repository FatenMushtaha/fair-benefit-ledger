<?php

namespace App\Models;

use App\Enums\CycleApplicantStatus;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\CycleApplicantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CycleApplicant extends Model
{
    /** @use HasFactory<CycleApplicantFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'cycle_id',
        'import_row_id',
        'person_id',
        'status',
        'verified_data_flags',
        'registered_at',
        'current_eligibility_evaluation_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => CycleApplicantStatus::class,
            'verified_data_flags' => 'array',
            'registered_at' => 'datetime',
        ];
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class);
    }

    public function importRow(): BelongsTo
    {
        return $this->belongsTo(ImportRow::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(EligibilityEvaluation::class);
    }

    public function currentEvaluation(): BelongsTo
    {
        return $this->belongsTo(EligibilityEvaluation::class, 'current_eligibility_evaluation_id');
    }
}

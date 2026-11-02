<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\ApplicantScoreFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicantScore extends Model
{
    /** @use HasFactory<ApplicantScoreFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'cycle_id',
        'cycle_run_id',
        'cycle_applicant_id',
        'criterion_points',
        'weighted_numerator',
        'display_score',
        'missing_flags',
        'reason_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'criterion_points' => 'array',
            'weighted_numerator' => 'integer',
            'display_score' => 'decimal:2',
            'missing_flags' => 'array',
            'reason_snapshot' => 'array',
        ];
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class);
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(CycleRun::class, 'cycle_run_id');
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(CycleApplicant::class, 'cycle_applicant_id');
    }
}

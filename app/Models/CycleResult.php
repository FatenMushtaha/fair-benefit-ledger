<?php

namespace App\Models;

use App\Enums\CycleResultOutcome;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\CycleResultFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CycleResult extends Model
{
    /** @use HasFactory<CycleResultFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'cycle_id',
        'cycle_run_id',
        'cycle_applicant_id',
        'rank',
        'outcome',
        'tie_values',
        'cutoff_marker',
        'reason_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'outcome' => CycleResultOutcome::class,
            'tie_values' => 'array',
            'cutoff_marker' => 'boolean',
            'reason_snapshot' => 'array',
            'rank' => 'integer',
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

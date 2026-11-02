<?php

namespace App\Models;

use App\Enums\CycleRunStatus;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\CycleRunFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CycleRun extends Model
{
    /** @use HasFactory<CycleRunFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'cycle_id',
        'rule_version_id',
        'run_sequence',
        'input_fingerprint',
        'status',
        'started_at',
        'finished_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => CycleRunStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'run_sequence' => 'integer',
        ];
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class);
    }

    public function ruleVersion(): BelongsTo
    {
        return $this->belongsTo(RuleVersion::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(ApplicantScore::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(CycleResult::class);
    }
}

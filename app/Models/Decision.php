<?php

namespace App\Models;

use App\Enums\DecisionOutcome;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\DecisionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Decision extends Model
{
    /** @use HasFactory<DecisionFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'cycle_id',
        'cycle_run_id',
        'cycle_result_id',
        'outcome',
        'reason',
        'actor_id',
        'decided_at',
        'supersedes_id',
    ];

    protected function casts(): array
    {
        return [
            'outcome' => DecisionOutcome::class,
            'decided_at' => 'datetime',
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

    public function result(): BelongsTo
    {
        return $this->belongsTo(CycleResult::class, 'cycle_result_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function supersedes(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supersedes_id');
    }
}

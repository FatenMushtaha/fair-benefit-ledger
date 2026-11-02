<?php

namespace App\Models;

use App\Enums\EligibilityStatus;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\EligibilityEvaluationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EligibilityEvaluation extends Model
{
    /** @use HasFactory<EligibilityEvaluationFactory> */
    use BelongsToOrganization, HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'cycle_id',
        'cycle_applicant_id',
        'rule_version_id',
        'evaluation_date',
        'input_fingerprint',
        'status',
        'reasons',
    ];

    protected function casts(): array
    {
        return [
            'status' => EligibilityStatus::class,
            'evaluation_date' => 'date',
            'reasons' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class);
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(CycleApplicant::class, 'cycle_applicant_id');
    }

    public function ruleVersion(): BelongsTo
    {
        return $this->belongsTo(RuleVersion::class);
    }
}

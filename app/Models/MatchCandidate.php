<?php

namespace App\Models;

use App\Enums\MatchConfidenceBand;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\MatchCandidateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchCandidate extends Model
{
    /** @use HasFactory<MatchCandidateFactory> */
    use BelongsToOrganization, HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'import_row_id',
        'person_id',
        'method',
        'confidence_band',
        'reason_codes',
        'blocking_key',
        'match_policy_version',
        'normalizer_version',
    ];

    protected function casts(): array
    {
        return [
            'confidence_band' => MatchConfidenceBand::class,
            'reason_codes' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function importRow(): BelongsTo
    {
        return $this->belongsTo(ImportRow::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}

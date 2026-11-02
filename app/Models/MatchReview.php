<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\MatchReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchReview extends Model
{
    /** @use HasFactory<MatchReviewFactory> */
    use BelongsToOrganization, HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'import_row_id',
        'candidate_id',
        'reviewer_id',
        'outcome',
        'reason',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function importRow(): BelongsTo
    {
        return $this->belongsTo(ImportRow::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(MatchCandidate::class, 'candidate_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}

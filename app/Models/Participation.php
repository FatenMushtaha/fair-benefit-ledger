<?php

namespace App\Models;

use App\Enums\ParticipationStatus;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\ParticipationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Participation extends Model
{
    /** @use HasFactory<ParticipationFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'person_id',
        'program_id',
        'import_row_id',
        'source_decision_id',
        'status',
        'start_date',
        'end_date',
        'completed_at',
        'withdrawn_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ParticipationStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'completed_at' => 'datetime',
            'withdrawn_at' => 'datetime',
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }
}

<?php

namespace App\Models;

use App\Enums\ImportBatchPurpose;
use App\Enums\ImportBatchStatus;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\ImportBatchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    /** @use HasFactory<ImportBatchFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'purpose',
        'cycle_id',
        'program_id',
        'uploader_id',
        'private_path',
        'format',
        'status',
        'counts',
        'cursor',
        'quality_summary',
        'error_summary',
    ];

    protected function casts(): array
    {
        return [
            'purpose' => ImportBatchPurpose::class,
            'status' => ImportBatchStatus::class,
            'counts' => 'array',
            'quality_summary' => 'array',
            'error_summary' => 'array',
        ];
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(ImportRow::class);
    }
}

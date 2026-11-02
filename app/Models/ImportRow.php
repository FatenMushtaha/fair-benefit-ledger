<?php

namespace App\Models;

use App\Enums\ImportRowStatus;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\ImportRowFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportRow extends Model
{
    /** @use HasFactory<ImportRowFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'import_batch_id',
        'row_number',
        'raw_payload',
        'mapped_payload',
        'cleaned_payload',
        'status',
        'errors',
        'person_id',
    ];

    protected function casts(): array
    {
        return [
            'raw_payload' => 'array',
            'mapped_payload' => 'array',
            'cleaned_payload' => 'array',
            'status' => ImportRowStatus::class,
            'errors' => 'array',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}

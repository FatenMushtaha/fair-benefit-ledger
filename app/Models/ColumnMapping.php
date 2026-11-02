<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\ColumnMappingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ColumnMapping extends Model
{
    /** @use HasFactory<ColumnMappingFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'owner_id',
        'source_headers',
        'canonical_map',
        'locale_hints',
        'approved',
    ];

    protected function casts(): array
    {
        return [
            'source_headers' => 'array',
            'canonical_map' => 'array',
            'locale_hints' => 'array',
            'approved' => 'boolean',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}

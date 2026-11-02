<?php

namespace App\Models;

use App\Enums\RuleSetStatus;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\RuleSetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RuleSet extends Model
{
    /** @use HasFactory<RuleSetFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = ['name', 'purpose', 'status', 'current_version_id'];

    protected function casts(): array
    {
        return [
            'status' => RuleSetStatus::class,
        ];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(RuleVersion::class);
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(RuleVersion::class, 'current_version_id');
    }
}

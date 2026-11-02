<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\RuleVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class RuleVersion extends Model
{
    /** @use HasFactory<RuleVersionFactory> */
    use BelongsToOrganization, HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = ['rule_set_id', 'version', 'config', 'created_by'];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'config' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(static function (self $model): void {
            if ($model->isUsedByCycle()) {
                throw new LogicException('A rule version used by a cycle cannot be changed.');
            }
        });

        static::deleting(static function (self $model): void {
            if ($model->isUsedByCycle()) {
                throw new LogicException('A rule version used by a cycle cannot be deleted.');
            }
        });
    }

    public function ruleSet(): BelongsTo
    {
        return $this->belongsTo(RuleSet::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function cycles(): HasMany
    {
        return $this->hasMany(Cycle::class, 'rule_version_id');
    }

    public function isUsedByCycle(): bool
    {
        return $this->cycles()->exists();
    }
}

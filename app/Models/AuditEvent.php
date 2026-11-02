<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\AuditEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class AuditEvent extends Model
{
    /** @use HasFactory<AuditEventFactory> */
    use BelongsToOrganization, HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'actor_id',
        'event_type',
        'subject_type',
        'subject_id',
        'reason',
        'payload',
        'occurred_at',
        'correlation_id',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'occurred_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(static function (): never {
            throw new LogicException('Audit events are append-only.');
        });

        static::deleting(static function (): never {
            throw new LogicException('Audit events are append-only.');
        });
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}

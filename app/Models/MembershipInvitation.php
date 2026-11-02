<?php

namespace App\Models;

use App\Enums\InvitationStatus;
use App\Enums\MembershipRole;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\MembershipInvitationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembershipInvitation extends Model
{
    /** @use HasFactory<MembershipInvitationFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'invited_by',
        'email',
        'role_key',
        'token_hash',
        'reason',
        'status',
        'expires_at',
        'accepted_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'role_key' => MembershipRole::class,
            'status' => InvitationStatus::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isUsable(): bool
    {
        return $this->status === InvitationStatus::Pending
            && $this->accepted_at === null
            && $this->revoked_at === null
            && $this->expires_at->isFuture();
    }
}

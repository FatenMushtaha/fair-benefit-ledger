<?php

namespace App\Models;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\OrganizationMembershipFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationMembership extends Model
{
    /** @use HasFactory<OrganizationMembershipFactory> */
    use BelongsToOrganization, HasFactory;

    protected $table = 'organization_user';

    protected $fillable = ['user_id', 'role_key', 'status'];

    protected function casts(): array
    {
        return [
            'role_key' => MembershipRole::class,
            'status' => MembershipStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', MembershipStatus::Active->value);
    }

    public function isActive(): bool
    {
        return $this->status === MembershipStatus::Active;
    }
}

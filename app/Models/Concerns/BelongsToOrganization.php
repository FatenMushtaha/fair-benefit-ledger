<?php

namespace App\Models\Concerns;

use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Support\OrganizationContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

trait BelongsToOrganization
{
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    protected static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope(new OrganizationScope);

        static::creating(static function ($model): void {
            $organizationId = app(OrganizationContext::class)->id();

            if ($organizationId === null) {
                throw new LogicException('A validated organization context is required.');
            }

            $model->setAttribute('organization_id', $organizationId);
        });

        static::updating(static function ($model): void {
            if ($model->isDirty('organization_id')) {
                throw new LogicException('A tenant record cannot move between organizations.');
            }
        });
    }
}

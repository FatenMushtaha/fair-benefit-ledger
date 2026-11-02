<?php

namespace App\Support;

use App\Models\Organization;
use Closure;
use LogicException;

class OrganizationContext
{
    public const SESSION_KEY = 'active_organization_id';

    private ?int $organizationId = null;

    public function id(): ?int
    {
        return $this->organizationId;
    }

    public function has(): bool
    {
        return $this->organizationId !== null;
    }

    public function set(Organization $organization): void
    {
        $this->organizationId = (int) $organization->getKey();
    }

    public function clear(): void
    {
        $this->organizationId = null;
    }

    public function requireId(): int
    {
        if ($this->organizationId === null) {
            throw new LogicException('A validated organization context is required.');
        }

        return $this->organizationId;
    }

    public function run(Organization $organization, Closure $callback): mixed
    {
        $previousId = $this->organizationId;
        $this->set($organization);

        try {
            return $callback();
        } finally {
            $this->organizationId = $previousId;
        }
    }
}

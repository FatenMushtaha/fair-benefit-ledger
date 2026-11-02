<?php

namespace App\Enums;

enum MembershipStatus: string
{
    case Active = 'active';
    case Deactivated = 'deactivated';

    public function isActive(): bool
    {
        return $this === self::Active;
    }
}

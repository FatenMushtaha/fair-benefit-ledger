<?php

namespace App\Enums;

enum RuleSetStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';
}

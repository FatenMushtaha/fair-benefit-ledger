<?php

namespace App\Enums;

enum CycleStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Closed = 'closed';
    case Archived = 'archived';
}

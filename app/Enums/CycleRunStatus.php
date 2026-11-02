<?php

namespace App\Enums;

enum CycleRunStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';
}

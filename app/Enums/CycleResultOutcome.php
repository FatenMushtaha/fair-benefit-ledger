<?php

namespace App\Enums;

enum CycleResultOutcome: string
{
    case Accepted = 'accepted';
    case Waitlisted = 'waitlisted';
    case Rejected = 'rejected';
    case Exception = 'exception';
}

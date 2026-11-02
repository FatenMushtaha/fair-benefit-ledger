<?php

namespace App\Enums;

enum DecisionOutcome: string
{
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Exception = 'exception';
}

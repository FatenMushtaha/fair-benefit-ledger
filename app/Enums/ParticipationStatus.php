<?php

namespace App\Enums;

enum ParticipationStatus: string
{
    case Accepted = 'accepted';
    case Active = 'active';
    case Completed = 'completed';
    case Withdrawn = 'withdrawn';
    case Cancelled = 'cancelled';
}

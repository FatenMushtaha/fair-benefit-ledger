<?php

namespace App\Enums;

enum EligibilityStatus: string
{
    case Eligible = 'eligible';
    case Ineligible = 'ineligible';
    case NeedsReview = 'needs_review';
}

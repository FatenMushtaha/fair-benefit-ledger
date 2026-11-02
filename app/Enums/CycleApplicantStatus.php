<?php

namespace App\Enums;

enum CycleApplicantStatus: string
{
    case Pending = 'pending';
    case Matched = 'matched';
    case Reviewed = 'reviewed';
    case Ineligible = 'ineligible';
    case Eligible = 'eligible';
    case NeedsReview = 'needs_review';
}

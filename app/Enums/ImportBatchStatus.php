<?php

namespace App\Enums;

enum ImportBatchStatus: string
{
    case Uploaded = 'uploaded';
    case NeedsConfiguration = 'needs_configuration';
    case Preflighted = 'preflighted';
    case Processing = 'processing';
    case NeedsReview = 'needs_review';
    case Validated = 'validated';
    case Failed = 'failed';
}

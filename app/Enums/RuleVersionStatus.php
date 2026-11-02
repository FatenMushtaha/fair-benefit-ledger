<?php

namespace App\Enums;

enum RuleVersionStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}

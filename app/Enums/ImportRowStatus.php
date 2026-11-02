<?php

namespace App\Enums;

enum ImportRowStatus: string
{
    case Staged = 'staged';
    case Valid = 'valid';
    case Invalid = 'invalid';
    case Corrected = 'corrected';
}

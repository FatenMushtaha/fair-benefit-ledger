<?php

namespace App\Enums;

enum ImportBatchPurpose: string
{
    case CycleApplicants = 'cycle_applicants';
    case HistoricalParticipation = 'historical_participation';
}

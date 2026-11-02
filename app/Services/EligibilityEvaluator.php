<?php

namespace App\Services;

use App\Enums\EligibilityStatus;
use App\Enums\ParticipationStatus;
use App\Models\Cycle;
use App\Models\CycleApplicant;
use App\Models\Participation;
use App\Models\RuleVersion;
use Carbon\Carbon;

class EligibilityEvaluator
{
    /**
     * @return array{status: string, reasons: array<int, string>, input_fingerprint: string}
     */
    public function evaluate(Cycle $cycle, CycleApplicant $applicant, RuleVersion $ruleVersion): array
    {
        $reasons = [];
        $status = EligibilityStatus::Eligible->value;
        $config = is_array($ruleVersion->config) ? $ruleVersion->config : [];

        if ($applicant->person_id === null) {
            return $this->result(EligibilityStatus::NeedsReview->value, ['unresolved_identity'], $cycle, $applicant, $ruleVersion);
        }

        $activeLimit = (int) ($config['active_project_limit'] ?? 1);
        $waitingMonths = (int) ($config['waiting_months'] ?? 12);

        $activeCount = Participation::query()
            ->where('organization_id', $applicant->organization_id)
            ->where('person_id', $applicant->person_id)
            ->whereIn('status', [ParticipationStatus::Accepted->value, ParticipationStatus::Active->value])
            ->count();

        if ($activeCount >= $activeLimit) {
            $status = EligibilityStatus::Ineligible->value;
            $reasons[] = 'active_project_limit';
        }

        $completed = Participation::query()
            ->where('organization_id', $applicant->organization_id)
            ->where('person_id', $applicant->person_id)
            ->where('status', ParticipationStatus::Completed->value)
            ->get();

        foreach ($completed as $participation) {
            if ($participation->end_date === null) {
                $status = EligibilityStatus::NeedsReview->value;
                $reasons[] = 'missing_completion_end_date';

                continue;
            }

            $threshold = Carbon::instance($participation->end_date)->addMonthsNoOverflow($waitingMonths);
            if ($cycle->start_date === null) {
                $status = EligibilityStatus::NeedsReview->value;
                $reasons[] = 'missing_cycle_start_date';

                continue;
            }

            if (Carbon::instance($cycle->start_date)->lt($threshold)) {
                if ($status !== EligibilityStatus::NeedsReview->value) {
                    $status = EligibilityStatus::Ineligible->value;
                }
                $reasons[] = 'waiting_period';
            }
        }

        $reasons = array_values(array_unique($reasons));

        return $this->result($status, $reasons, $cycle, $applicant, $ruleVersion);
    }

    private function result(string $status, array $reasons, Cycle $cycle, CycleApplicant $applicant, RuleVersion $ruleVersion): array
    {
        $participations = Participation::query()
            ->where('organization_id', $applicant->organization_id)
            ->where('person_id', $applicant->person_id)
            ->get(['id', 'status', 'end_date'])
            ->map(fn ($p) => [$p->id, $p->status?->value, $p->end_date?->toDateString()])
            ->all();

        $fingerprint = hash('sha256', json_encode([
            'applicant' => $applicant->id,
            'person' => $applicant->person_id,
            'cycle' => $cycle->id,
            'rule_version' => $ruleVersion->id,
            'evaluation_date' => $cycle->start_date?->toDateString(),
            'participations' => $participations,
            'config' => $ruleVersion->config,
        ]));

        return [
            'status' => $status,
            'reasons' => $reasons,
            'input_fingerprint' => $fingerprint,
        ];
    }
}

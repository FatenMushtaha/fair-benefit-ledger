<?php

namespace App\Jobs;

use App\Enums\CycleResultOutcome;
use App\Enums\CycleRunStatus;
use App\Enums\EligibilityStatus;
use App\Models\ApplicantScore;
use App\Models\Cycle;
use App\Models\CycleApplicant;
use App\Models\CycleResult;
use App\Models\CycleRun;
use App\Models\Organization;
use App\Services\RankingRuleValidator;
use App\Services\ScoreApplicant;
use App\Support\OrganizationContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunCycleScoring implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $organizationId,
        public int $cycleId,
        public int $userId,
    ) {}

    public function handle(OrganizationContext $context, ScoreApplicant $scorer): void
    {
        $organization = Organization::findOrFail($this->organizationId);

        $context->run($organization, function () use ($scorer): void {
            $cycle = Cycle::findOrFail($this->cycleId);
            $ruleVersion = $cycle->ruleVersion;
            if ($ruleVersion === null) {
                throw new \RuntimeException('Cycle has no rule version.');
            }

            $errors = app(RankingRuleValidator::class)->validate(is_array($ruleVersion->config) ? $ruleVersion->config : []);
            if ($errors !== []) {
                throw new \RuntimeException(implode('; ', $errors));
            }

            $run = CycleRun::create([
                'organization_id' => $this->organizationId,
                'cycle_id' => $cycle->id,
                'rule_version_id' => $ruleVersion->id,
                'run_sequence' => CycleRun::where('organization_id', $this->organizationId)->where('cycle_id', $cycle->id)->count() + 1,
                'input_fingerprint' => hash('sha256', json_encode(['cycle' => $cycle->id, 'rule_version' => $ruleVersion->id])),
                'status' => CycleRunStatus::Pending,
                'started_at' => now(),
                'created_by' => $this->userId,
            ]);

            $applicants = CycleApplicant::query()
                ->where('organization_id', $this->organizationId)
                ->where('cycle_id', $cycle->id)
                ->whereHas('currentEvaluation', fn ($q) => $q->where('status', EligibilityStatus::Eligible->value))
                ->get();

            $scored = [];
            foreach ($applicants as $applicant) {
                $inputs = is_array($applicant->verified_data_flags) ? $applicant->verified_data_flags : [];
                $result = $scorer->score(is_array($ruleVersion->config) ? $ruleVersion->config : [], $inputs);

                ApplicantScore::create([
                    'organization_id' => $this->organizationId,
                    'cycle_id' => $cycle->id,
                    'cycle_run_id' => $run->id,
                    'cycle_applicant_id' => $applicant->id,
                    'criterion_points' => $result['criterion_points'],
                    'weighted_numerator' => $result['weighted_numerator'],
                    'display_score' => $result['display_score'],
                    'missing_flags' => $result['missing_flags'],
                    'reason_snapshot' => $result['reasons'],
                ]);

                $scored[] = [
                    'applicant' => $applicant,
                    'numerator' => $result['weighted_numerator'],
                    'registered_at' => $applicant->registered_at,
                ];
            }

            usort($scored, static function (array $a, array $b): int {
                if ($a['numerator'] !== $b['numerator']) {
                    return $b['numerator'] <=> $a['numerator'];
                }
                $ra = $a['registered_at']?->timestamp ?? 0;
                $rb = $b['registered_at']?->timestamp ?? 0;
                if ($ra !== $rb) {
                    return $ra <=> $rb;
                }

                return $a['applicant']->id <=> $b['applicant']->id;
            });

            $capacity = (int) ($cycle->available_quantity ?? 0);
            $rank = 1;
            foreach ($scored as $index => $item) {
                $outcome = $index < $capacity
                    ? CycleResultOutcome::Accepted->value
                    : CycleResultOutcome::Waitlisted->value;

                CycleResult::create([
                    'organization_id' => $this->organizationId,
                    'cycle_id' => $cycle->id,
                    'cycle_run_id' => $run->id,
                    'cycle_applicant_id' => $item['applicant']->id,
                    'rank' => $rank++,
                    'outcome' => $outcome,
                    'tie_values' => [
                        'numerator' => $item['numerator'],
                        'registered_at' => $item['registered_at']?->toDateTimeString(),
                        'applicant_id' => $item['applicant']->id,
                    ],
                    'cutoff_marker' => $index === $capacity - 1 && $capacity > 0,
                    'reason_snapshot' => [],
                ]);
            }

            $run->update([
                'status' => CycleRunStatus::Completed,
                'finished_at' => now(),
            ]);

            $cycle->update(['current_cycle_run_id' => $run->id]);
        });
    }
}

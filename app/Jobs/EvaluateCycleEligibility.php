<?php

namespace App\Jobs;

use App\Models\Cycle;
use App\Models\CycleApplicant;
use App\Models\EligibilityEvaluation;
use App\Models\Organization;
use App\Services\EligibilityEvaluator;
use App\Support\OrganizationContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EvaluateCycleEligibility implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $organizationId,
        public int $cycleId,
    ) {}

    public function handle(OrganizationContext $context, EligibilityEvaluator $evaluator): void
    {
        $organization = Organization::findOrFail($this->organizationId);

        $context->run($organization, function () use ($evaluator): void {
            $cycle = Cycle::findOrFail($this->cycleId);

            foreach (CycleApplicant::where('organization_id', $this->organizationId)->where('cycle_id', $cycle->id)->get() as $applicant) {
                if ($applicant->person_id === null) {
                    continue;
                }

                $ruleVersion = $cycle->ruleVersion;
                if ($ruleVersion === null) {
                    continue;
                }

                $result = $evaluator->evaluate($cycle, $applicant, $ruleVersion);

                $evaluation = EligibilityEvaluation::create([
                    'organization_id' => $this->organizationId,
                    'cycle_id' => $cycle->id,
                    'cycle_applicant_id' => $applicant->id,
                    'rule_version_id' => $ruleVersion->id,
                    'evaluation_date' => $cycle->start_date,
                    'input_fingerprint' => $result['input_fingerprint'],
                    'status' => $result['status'],
                    'reasons' => $result['reasons'],
                ]);

                $applicant->update(['current_eligibility_evaluation_id' => $evaluation->id]);
            }
        });
    }
}

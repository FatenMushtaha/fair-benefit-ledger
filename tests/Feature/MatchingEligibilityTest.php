<?php

namespace Tests\Feature;

use App\Enums\EligibilityStatus;
use App\Enums\ParticipationStatus;
use App\Models\Cycle;
use App\Models\CycleApplicant;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\MatchCandidate;
use App\Models\Participation;
use App\Models\Person;
use App\Models\PersonName;
use App\Models\Program;
use App\Models\RuleSet;
use App\Models\RuleVersion;
use App\Services\CandidateGenerator;
use App\Services\EligibilityEvaluator;
use App\Services\NameNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsOrganizationContext;
use Tests\TestCase;

class MatchingEligibilityTest extends TestCase
{
    use BuildsOrganizationContext, RefreshDatabase;

    public function test_name_normalization_is_deterministic(): void
    {
        $normalizer = new NameNormalizer;
        $input = '  أَحْمَد  محمد  ';
        $this->assertSame($normalizer->normalize($input), $normalizer->normalize($input));
        $this->assertSame($normalizer->tokenSortedKey('ب أ'), $normalizer->tokenSortedKey('أ ب'));
    }

    public function test_candidate_generation_is_tenant_bound(): void
    {
        $orgA = $this->makeOrganization('Org A');
        $orgB = $this->makeOrganization('Org B');

        $personA = $this->inOrganization($orgA, fn () => Person::factory()->create());
        $this->inOrganization($orgA, fn () => PersonName::factory()->create([
            'person_id' => $personA->id,
            'name' => 'Alice Smith',
            'normalized_name' => 'alice smith',
            'blocking_key' => 'alice smith',
        ]));

        $personB = $this->inOrganization($orgB, fn () => Person::factory()->create());
        $this->inOrganization($orgB, fn () => PersonName::factory()->create([
            'person_id' => $personB->id,
            'name' => 'Alice Smith',
            'normalized_name' => 'alice smith',
            'blocking_key' => 'alice smith',
        ]));

        $batchA = $this->inOrganization($orgA, fn () => ImportBatch::factory()->create());
        $row = $this->inOrganization($orgA, fn () => ImportRow::factory()->create([
            'import_batch_id' => $batchA->id,
            'raw_payload' => ['name' => 'Alice Smith'],
            'cleaned_payload' => ['name' => 'Alice Smith'],
        ]));

        $this->inOrganization($orgA, fn () => app(CandidateGenerator::class)->generate($row));

        $this->inOrganization($orgA, function () use ($row, $personA): void {
            $this->assertSame(1, MatchCandidate::where('import_row_id', $row->id)->count());
            $this->assertSame($personA->id, MatchCandidate::where('import_row_id', $row->id)->first()->person_id);
        });
    }

    public function test_eligibility_waiting_period_boundary(): void
    {
        $org = $this->makeOrganization();
        $program = $this->inOrganization($org, fn () => Program::factory()->create());
        $ruleSet = $this->inOrganization($org, fn () => RuleSet::factory()->create());
        $ruleVersion = $this->inOrganization($org, fn () => RuleVersion::factory()->create([
            'rule_set_id' => $ruleSet->id,
            'config' => ['waiting_months' => 12, 'active_project_limit' => 1],
        ]));
        $cycle = $this->inOrganization($org, fn () => Cycle::factory()->create([
            'program_id' => $program->id,
            'rule_version_id' => $ruleVersion->id,
            'start_date' => '2025-06-15',
        ]));

        $person = $this->inOrganization($org, fn () => Person::factory()->create());
        $batch = $this->inOrganization($org, fn () => ImportBatch::factory()->create());
        $importRow = $this->inOrganization($org, fn () => ImportRow::factory()->create([
            'import_batch_id' => $batch->id,
            'raw_payload' => ['name' => 'Test'],
        ]));
        $applicant = $this->inOrganization($org, fn () => CycleApplicant::factory()->create([
            'cycle_id' => $cycle->id,
            'import_row_id' => $importRow->id,
            'person_id' => $person->id,
        ]));

        $this->inOrganization($org, function () use ($person, $program): void {
            Participation::factory()->create([
                'person_id' => $person->id,
                'program_id' => $program->id,
                'status' => ParticipationStatus::Completed,
                'end_date' => '2024-06-16',
            ]);
        });

        $result = $this->inOrganization($org, fn () => app(EligibilityEvaluator::class)->evaluate($cycle, $applicant, $ruleVersion));
        $this->assertSame(EligibilityStatus::Ineligible->value, $result['status']);
    }
}

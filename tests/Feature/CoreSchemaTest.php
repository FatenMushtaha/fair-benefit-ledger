<?php

namespace Tests\Feature;

use App\Models\Cycle;
use App\Models\Person;
use App\Models\Program;
use App\Models\RuleSet;
use App\Models\RuleVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsOrganizationContext;
use Tests\TestCase;

class CoreSchemaTest extends TestCase
{
    use BuildsOrganizationContext, RefreshDatabase;

    public function test_tenant_isolation_for_core_records(): void
    {
        $orgA = $this->makeOrganization('Org A');
        $orgB = $this->makeOrganization('Org B');

        $person = $this->inOrganization($orgA, fn () => Person::factory()->create());
        $program = $this->inOrganization($orgA, fn () => Program::factory()->create());
        $ruleSet = $this->inOrganization($orgA, fn () => RuleSet::factory()->create());
        $ruleVersion = $this->inOrganization($orgA, fn () => RuleVersion::factory()->create([
            'rule_set_id' => $ruleSet->id,
        ]));
        $cycle = $this->inOrganization($orgA, fn () => Cycle::factory()->create([
            'program_id' => $program->id,
            'rule_version_id' => $ruleVersion->id,
        ]));

        $this->inOrganization($orgB, function () use ($person, $program, $cycle): void {
            $this->assertNull(Person::find($person->id));
            $this->assertNull(Program::find($program->id));
            $this->assertNull(Cycle::find($cycle->id));
        });
    }

    public function test_rule_version_used_by_cycle_cannot_be_changed(): void
    {
        $org = $this->makeOrganization();
        $program = $this->inOrganization($org, fn () => Program::factory()->create());
        $ruleSet = $this->inOrganization($org, fn () => RuleSet::factory()->create());
        $ruleVersion = $this->inOrganization($org, fn () => RuleVersion::factory()->create([
            'rule_set_id' => $ruleSet->id,
        ]));
        $this->inOrganization($org, fn () => Cycle::factory()->create([
            'program_id' => $program->id,
            'rule_version_id' => $ruleVersion->id,
        ]));

        $this->expectException(\LogicException::class);
        $this->inOrganization($org, function () use ($ruleVersion): void {
            $ruleVersion->update(['version' => 2]);
        });
    }
}

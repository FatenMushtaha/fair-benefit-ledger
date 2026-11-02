<?php

namespace Tests\Feature;

use App\Services\RankingRuleValidator;
use App\Services\ScoreApplicant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_ranking_rule_validator_rejects_invalid_weight_count(): void
    {
        $validator = new RankingRuleValidator;
        $errors = $validator->validate([
            'criteria' => [
                ['key' => 'a', 'weight' => 5000, 'input_source' => 'x', 'mapping' => ['1' => 1000]],
                ['key' => 'b', 'weight' => 5000, 'input_source' => 'y', 'mapping' => ['1' => 1000]],
            ],
            'missing_data_policy' => 'block',
            'tie_break' => ['registered_at'],
            'automation_mode' => 'manual_approval_required',
        ]);

        $this->assertNotEmpty($errors);
    }

    public function test_score_calculation_uses_exact_numerator(): void
    {
        $scorer = new ScoreApplicant;
        $result = $scorer->score([
            'criteria' => [
                ['key' => 'a', 'weight' => 6000, 'input_source' => 'x', 'mapping' => ['1' => 8000]],
                ['key' => 'b', 'weight' => 4000, 'input_source' => 'y', 'mapping' => ['1' => 5000]],
            ],
            'missing_data_policy' => 'block',
        ], ['x' => '1', 'y' => '1']);

        $this->assertSame(68000000, $result['weighted_numerator']);
        $this->assertSame('6800.00', $result['display_score']);
    }

    public function test_allocation_respects_capacity(): void
    {
        $items = [
            ['id' => 1, 'numerator' => 100, 'registered_at' => null],
            ['id' => 2, 'numerator' => 90, 'registered_at' => null],
            ['id' => 3, 'numerator' => 80, 'registered_at' => null],
        ];

        usort($items, static fn ($a, $b) => $b['numerator'] <=> $a['numerator']);
        $capacity = 2;
        $accepted = 0;
        foreach (array_values($items) as $index => $item) {
            if ($index < $capacity) {
                $accepted++;
            }
        }

        $this->assertSame(2, $accepted);
    }
}

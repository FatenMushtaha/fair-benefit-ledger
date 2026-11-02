<?php

namespace Database\Factories;

use App\Enums\MatchConfidenceBand;
use App\Models\MatchCandidate;
use Illuminate\Database\Eloquent\Factories\Factory;

class MatchCandidateFactory extends Factory
{
    protected $model = MatchCandidate::class;

    public function definition(): array
    {
        return [
            'import_row_id' => null,
            'person_id' => null,
            'method' => 'name_blocking',
            'confidence_band' => MatchConfidenceBand::Medium,
            'reason_codes' => ['name_blocking_match'],
            'blocking_key' => 'test',
            'match_policy_version' => '1',
            'normalizer_version' => '1-1',
        ];
    }
}

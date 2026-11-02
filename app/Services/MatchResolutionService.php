<?php

namespace App\Services;

use App\Enums\MatchConfidenceBand;
use App\Models\ImportRow;
use App\Models\MatchCandidate;
use App\Models\MatchReview;
use App\Models\User;

class MatchResolutionService
{
    public function autoLinkHigh(ImportRow $row): void
    {
        $high = MatchCandidate::query()
            ->where('organization_id', $row->organization_id)
            ->where('import_row_id', $row->id)
            ->where('confidence_band', MatchConfidenceBand::High->value)
            ->get();

        if ($high->count() === 1) {
            $row->update(['person_id' => $high->first()->person_id]);
        }
    }

    public function recordReview(
        ImportRow $row,
        ?MatchCandidate $candidate,
        User $reviewer,
        string $outcome,
        string $reason,
    ): MatchReview {
        return MatchReview::create([
            'organization_id' => $row->organization_id,
            'import_row_id' => $row->id,
            'candidate_id' => $candidate?->id,
            'reviewer_id' => $reviewer->id,
            'outcome' => $outcome,
            'reason' => $reason,
            'reviewed_at' => now(),
        ]);
    }
}

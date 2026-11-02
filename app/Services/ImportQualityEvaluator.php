<?php

namespace App\Services;

use App\Enums\ImportBatchStatus;
use App\Enums\ImportRowStatus;
use App\Models\ImportBatch;

class ImportQualityEvaluator
{
    /**
     * @return array{total: int, valid: int, invalid: int, status: string}
     */
    public function evaluate(ImportBatch $batch, int $invalidThreshold = 0): array
    {
        $total = $batch->rows()->count();
        $invalid = $batch->rows()->where('status', ImportRowStatus::Invalid->value)->count();
        $valid = $total - $invalid;

        $status = $invalid > $invalidThreshold
            ? ImportBatchStatus::NeedsReview->value
            : ImportBatchStatus::Validated->value;

        $summary = [
            'total' => $total,
            'valid' => $valid,
            'invalid' => $invalid,
        ];

        $batch->update([
            'quality_summary' => $summary,
            'status' => $status,
        ]);

        return $summary + ['status' => $status];
    }
}

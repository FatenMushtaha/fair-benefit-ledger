<?php

namespace App\Jobs;

use App\Enums\ImportBatchStatus;
use App\Enums\ImportRowStatus;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Organization;
use App\Services\ImportFileReader;
use App\Support\OrganizationContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ProcessImportBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $organizationId,
        public int $batchId,
    ) {}

    public function handle(OrganizationContext $context, ImportFileReader $reader): void
    {
        $organization = Organization::findOrFail($this->organizationId);
        $context->run($organization, function () use ($reader): void {
            $batch = ImportBatch::findOrFail($this->batchId);

            try {
                $path = Storage::disk('local')->path($batch->private_path);
                $format = $batch->format === 'xlsx' ? 'xlsx' : 'csv';
                $result = $reader->read($path, $format);

                if ($result['has_formula']) {
                    throw new \RuntimeException('Formula cells are not allowed.');
                }

                ImportRow::where('organization_id', $this->organizationId)
                    ->where('import_batch_id', $batch->id)
                    ->delete();

                foreach ($result['rows'] as $index => $row) {
                    ImportRow::create([
                        'organization_id' => $this->organizationId,
                        'import_batch_id' => $batch->id,
                        'row_number' => $index + 2,
                        'raw_payload' => array_values($row),
                        'status' => ImportRowStatus::Staged,
                    ]);
                }

                $batch->update([
                    'status' => ImportBatchStatus::Preflighted,
                    'counts' => [
                        'rows' => count($result['rows']),
                        'headers' => count($result['headers']),
                    ],
                    'error_summary' => null,
                ]);
            } catch (\Throwable $exception) {
                $batch->update([
                    'status' => ImportBatchStatus::Failed,
                    'error_summary' => ['message' => $exception->getMessage()],
                ]);
            }
        });
    }
}

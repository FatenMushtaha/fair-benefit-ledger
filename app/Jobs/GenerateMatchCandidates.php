<?php

namespace App\Jobs;

use App\Models\ImportBatch;
use App\Models\Organization;
use App\Services\CandidateGenerator;
use App\Support\OrganizationContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateMatchCandidates implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $organizationId,
        public int $batchId,
    ) {}

    public function handle(OrganizationContext $context, CandidateGenerator $generator): void
    {
        $organization = Organization::findOrFail($this->organizationId);

        $context->run($organization, function () use ($generator): void {
            $batch = ImportBatch::findOrFail($this->batchId);

            foreach ($batch->rows as $row) {
                $generator->generate($row);
            }
        });
    }
}

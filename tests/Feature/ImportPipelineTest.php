<?php

namespace Tests\Feature;

use App\Jobs\ProcessImportBatch;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsOrganizationContext;
use Tests\TestCase;

class ImportPipelineTest extends TestCase
{
    use BuildsOrganizationContext, RefreshDatabase;

    public function test_import_job_stages_rows_from_csv(): void
    {
        $organization = $this->makeOrganization();
        Storage::fake('local');
        $path = 'imports/'.$organization->id.'/test.csv';
        Storage::disk('local')->put($path, "name,email\nAlice,alice@example.test\nBob,bob@example.test\n");

        $batch = $this->inOrganization($organization, fn () => ImportBatch::factory()->create([
            'private_path' => $path,
            'format' => 'csv',
        ]));

        ProcessImportBatch::dispatchSync($organization->id, $batch->id);

        $this->assertSame('preflighted', $batch->fresh()->status->value);
        $this->inOrganization($organization, function () use ($batch): void {
            $this->assertSame(2, ImportRow::where('import_batch_id', $batch->id)->count());
        });
    }
}

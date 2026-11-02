<?php

namespace App\Http\Controllers;

use App\Enums\ImportBatchStatus;
use App\Enums\ImportRowStatus;
use App\Jobs\ProcessImportBatch;
use App\Models\ColumnMapping;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Services\AuditRecorder;
use App\Support\OrganizationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ImportController extends Controller
{
    public function index(Request $request): View
    {
        $batches = ImportBatch::query()
            ->where('organization_id', (int) session(OrganizationContext::SESSION_KEY))
            ->latest()
            ->get();

        return view('imports.index', ['batches' => $batches]);
    }

    public function create(): View
    {
        return view('imports.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:'.((int) config('imports.max_bytes', 10485760) / 1024)],
            'purpose' => ['required', 'in:cycle_applicants,historical_participation'],
            'cycle_id' => ['nullable', 'integer'],
            'program_id' => ['nullable', 'integer'],
        ]);

        $file = $request->file('file');
        $extension = strtolower((string) $file->getClientOriginalExtension());
        if (! in_array($extension, ['xlsx', 'csv'], true)) {
            return back()->withErrors(['file' => 'Unsupported file format.']);
        }

        $orgId = (int) session(OrganizationContext::SESSION_KEY);
        $path = $file->storeAs('imports/'.$orgId, (string) Str::uuid().'.'.$extension, 'local');

        $batch = ImportBatch::create([
            'organization_id' => $orgId,
            'purpose' => $validated['purpose'],
            'cycle_id' => $validated['cycle_id'] ?? null,
            'program_id' => $validated['program_id'] ?? null,
            'uploader_id' => $request->user()->id,
            'private_path' => $path,
            'format' => $extension,
            'status' => ImportBatchStatus::Uploaded,
        ]);

        ProcessImportBatch::dispatch($orgId, $batch->id);

        return redirect()->route('imports.show', $batch)->with('status', 'Import uploaded and queued.');
    }

    public function show(ImportBatch $batch): View
    {
        abort_unless((int) $batch->organization_id === (int) session(OrganizationContext::SESSION_KEY), 404);

        return view('imports.show', [
            'batch' => $batch,
            'rows' => $batch->rows()->orderBy('row_number')->get(),
        ]);
    }

    public function correct(Request $request, ImportRow $row, AuditRecorder $audit): RedirectResponse
    {
        abort_unless((int) $row->organization_id === (int) session(OrganizationContext::SESSION_KEY), 404);

        $validated = $request->validate([
            'mapped_payload' => ['nullable', 'array'],
            'cleaned_payload' => ['nullable', 'array'],
            'reason' => ['required', 'string', 'min:3'],
        ]);

        $row->update([
            'mapped_payload' => $validated['mapped_payload'] ?? $row->mapped_payload,
            'cleaned_payload' => $validated['cleaned_payload'] ?? $row->cleaned_payload,
            'status' => ImportRowStatus::Corrected,
        ]);

        $audit->record(
            (int) $row->organization_id,
            (int) $request->user()->id,
            'import.row.corrected',
            ImportRow::class,
            $row->id,
            $validated['reason'],
            [],
        );

        return back()->with('status', 'Row corrected.');
    }

    public function confirmMapping(Request $request, ImportBatch $batch, AuditRecorder $audit): RedirectResponse
    {
        abort_unless((int) $batch->organization_id === (int) session(OrganizationContext::SESSION_KEY), 404);

        $validated = $request->validate([
            'source_headers' => ['required', 'array'],
            'canonical_map' => ['required', 'array'],
            'reason' => ['required', 'string', 'min:3'],
        ]);

        $mapping = ColumnMapping::create([
            'organization_id' => $batch->organization_id,
            'owner_id' => $request->user()->id,
            'source_headers' => $validated['source_headers'],
            'canonical_map' => $validated['canonical_map'],
            'approved' => true,
        ]);

        $audit->record(
            (int) $batch->organization_id,
            (int) $request->user()->id,
            'import.mapping.confirmed',
            ColumnMapping::class,
            $mapping->id,
            $validated['reason'],
            ['batch_id' => $batch->id],
        );

        return back()->with('status', 'Mapping confirmed.');
    }
}

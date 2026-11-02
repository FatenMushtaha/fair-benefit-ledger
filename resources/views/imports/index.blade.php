<x-layouts.app :title="'Imports'">
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold">Imports</h1>
            <a href="{{ route('imports.create') }}" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white">New import</a>
        </div>
        <div class="space-y-2">
            @foreach ($batches as $batch)
                <a href="{{ route('imports.show', $batch) }}" class="block rounded-lg border border-slate-200 p-3 hover:bg-slate-50">
                    Batch #{{ $batch->id }} — {{ $batch->status->value }} — {{ $batch->format }}
                </a>
            @endforeach
        </div>
    </div>
</x-layouts.app>
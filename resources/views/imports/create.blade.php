<x-layouts.app :title="'New import'">
    <form method="POST" action="{{ route('imports.store') }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        <input type="file" name="file" accept=".xlsx,.csv" required>
        <select name="purpose" required>
            <option value="cycle_applicants">Cycle applicants</option>
            <option value="historical_participation">Historical participation</option>
        </select>
        <button type="submit" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white">Upload</button>
    </form>
</x-layouts.app>
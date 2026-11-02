<x-layouts.app :title="'Import batch'">
    <div class="space-y-4">
        <h1 class="text-xl font-semibold">Batch #{{ $batch->id }}</h1>
        <p>Status: {{ $batch->status->value }}</p>
        <table class="w-full text-sm">
            <thead><tr><th>Row</th><th>Status</th><th>Raw</th></tr></thead>
            <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row->row_number }}</td>
                    <td>{{ $row->status->value }}</td>
                    <td><pre>{{ json_encode($row->raw_payload) }}</pre></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.app>
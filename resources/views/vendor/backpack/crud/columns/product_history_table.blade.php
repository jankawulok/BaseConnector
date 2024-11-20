@php
    $history = App\Models\ProductHistory::where('product_auto_id', $entry->product_auto_id)
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function($item) {
            return [
                'date' => $item->created_at->format('Y-m-d H:i:s'),
                'field' => $item->field_name,
                'old' => $item->old_value,
                'new' => $item->new_value,
                'variant' => $item->variant_id ?: '-'
            ];
        });
@endphp

<div class="card">
    <div class="card-header">
        Change History
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Field</th>
                        <th>Old Value</th>
                        <th>New Value</th>
                        <th>Variant</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($history as $change)
                        <tr>
                            <td>{{ $change['date'] }}</td>
                            <td>{{ $change['field'] }}</td>
                            <td>{{ $change['old'] }}</td>
                            <td>{{ $change['new'] }}</td>
                            <td>{{ $change['variant'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">No changes recorded</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

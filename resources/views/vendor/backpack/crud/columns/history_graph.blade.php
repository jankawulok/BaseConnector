@php
    // Attempt to get the field name from various possible sources
    // 1. Explicitly passed 'field_name'
    // 2. From the column definition ('name' or 'field')
    // 3. Fallback to entry's field_name (raw to avoid accessor capitalization)
    $rawEntryField = $entry->getAttributes()['field_name'] ?? $entry->field_name;

    // Determine the field we should show history for
    $columnFieldName = $field_name ?? $column['field_name'] ?? null;

    if (!$columnFieldName && isset($column['name'])) {
        $columnFieldName = str_replace('_history', '', $column['name']);
    }

    $columnFieldName = $columnFieldName ?: $rawEntryField;
    $columnFieldName = strtolower($columnFieldName);

    // Product ID
    $productId = $product_auto_id ?? $entry->product_auto_id;

    // Get history for this specific field and product
    $historyQuery = App\Models\ProductHistory::where('product_auto_id', $productId)
        ->where('field_name', $columnFieldName);

    // Handle variant_id filtering - if the current entry has a variant, show history for that variant
    // If it doesn't, show history where variant_id is null or empty
    if (!empty($entry->variant_id)) {
        $historyQuery->where('variant_id', $entry->variant_id);
    } else {
        $historyQuery->where(function ($q) {
            $q->whereNull('variant_id')->orWhere('variant_id', '');
        });
    }

    $history = $historyQuery->orderBy('created_at', 'desc')->get();

    // Prepare data for the graph
    $graphData = $history->sortBy('created_at');
    $dates = $graphData->pluck('created_at')->map(function ($date) {
        return $date->format('Y-m-d H:i:s');
    })->values()->toJson();

    $values = $graphData->map(function ($item) {
        return (float) ($item->getAttributes()['new_value'] ?? $item->new_value);
    })->values()->toJson();
    $uniqueId = 'graph_' . strtolower(str_replace(' ', '_', $columnFieldName));
@endphp

<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                {{ ucfirst($columnFieldName) }} History Graph
                @if($entry->variant_id)
                    for Variant {{ $entry->variant_id }}
                @endif
            </div>
            <div class="card-body">
                <div style="height: 300px;">
                    <canvas id="{{ $uniqueId }}"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                {{ ucfirst($columnFieldName) }} Log
            </div>
            <div class="card-body p-0" style="max-height: 340px; overflow-y: auto;">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>From</th>
                                <th>To</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($history as $change)
                                <tr>
                                    <td>{{ $change->created_at->diffForHumans() }}</td>
                                    <td>{{ $change->old_value }}</td>
                                    <td>{{ $change->new_value }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No history found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('after_styles')
    <style>
        .table td,
        .table th {
            padding: 0.5rem;
            font-size: 0.9rem;
        }
    </style>
@endpush

@push('after_scripts')
    @once
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script
            src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
    @endonce
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('{{ $uniqueId }}').getContext('2d');
            const dates = {!! $dates !!};
            const values = {!! $values !!};

            if (dates.length === 0) return;

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: dates,
                    datasets: [{
                        label: '{{ ucfirst($columnFieldName) }}',
                        data: values,
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.1)',
                        tension: 0.1,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: false,
                            ticks: {
                                callback: function (value) {
                                    if ('{{ strtolower($columnFieldName) }}' === 'price') {
                                        return value.toFixed(2);
                                    }
                                    return value;
                                }
                            }
                        },
                        x: {
                            type: 'time',
                            time: {
                                unit: 'day',
                                displayFormats: {
                                    day: 'MMM d'
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    let value = context.raw;
                                    if ('{{ strtolower($columnFieldName) }}' === 'price') {
                                        return `Value: ${parseFloat(value).toFixed(2)}`;
                                    }
                                    return `Value: ${value}`;
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
@endpush
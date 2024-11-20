@php
    $history = App\Models\ProductHistory::where('product_auto_id', $entry->product_auto_id)
        ->where('field_name', $entry->field_name)
        ->where('variant_id', $entry->variant_id)
        ->orderBy('created_at', 'desc')
        ->get();

    // Prepare data for the graph
    $graphData = $history->sortBy('created_at');
    $dates = $graphData->pluck('created_at')->map(function($date) {
        return $date->format('Y-m-d H:i:s');
    })->toJson();

    $values = $graphData->pluck('new_value')->toJson();
@endphp

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                {{ ucfirst($entry->field_name) }} History Graph
                @if($entry->variant_id)
                    for Variant {{ $entry->variant_id }}
                @endif
            </div>
            <div class="card-body">
                <canvas id="historyGraph" style="width: 100%; height: 400px;"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                Change History
            </div>
            <div class="card-body p-0">
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
                            @foreach($history as $change)
                            <tr>
                                <td>{{ $change->created_at->format('Y-m-d H:i:s') }}</td>
                                <td>{{ $change->old_value }}</td>
                                <td>{{ $change->new_value }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('after_styles')
<style>
    .table td, .table th {
        padding: 0.5rem;
        font-size: 0.9rem;
    }
</style>
@endpush

@push('after_scripts')

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('historyGraph').getContext('2d');
    const dates = {!! $dates !!};
    const values = {!! $values !!};

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: dates,
            datasets: [{
                label: '{{ ucfirst($entry->field_name) }} Changes',
                data: values,
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1,
                fill: false,
                pointRadius: 5,
                pointHoverRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: false,
                    ticks: {
                        callback: function(value) {
                            if ('{{ $entry->field_name }}' === 'price') {
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
                            day: 'MMM D HH:mm'
                        }
                    },
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let value = context.raw;
                            if ('{{ $entry->field_name }}' === 'price') {
                                return `${value.toFixed(2)}`;
                            }
                            return value;
                        }
                    }
                }
            }
        }
    });
});
</script>
@endpush

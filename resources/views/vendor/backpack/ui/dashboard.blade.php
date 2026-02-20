@extends(backpack_view('blank'))

@php
    $widgets = [
        [
            'type' => 'card',
            'class' => 'card bg-primary text-white',
            'wrapper' => ['class' => 'col-sm-6 col-md-3'],
            'content' => [
                'header' => 'Integrations',
                'body' => '<h3>' . $total_integrations . '</h3>',
            ]
        ],
        [
            'type' => 'card',
            'class' => 'card bg-success text-white',
            'wrapper' => ['class' => 'col-sm-6 col-md-3'],
            'content' => [
                'header' => 'Products',
                'body' => '<h3>' . $total_products . '</h3>',
            ]
        ],
        [
            'type' => 'card',
            'class' => 'card bg-info text-white',
            'wrapper' => ['class' => 'col-sm-6 col-md-3'],
            'content' => [
                'header' => 'Categories',
                'body' => '<h3>' . $total_categories . '</h3>',
            ]
        ],
        [
            'type' => 'card',
            'class' => 'card bg-warning text-white',
            'wrapper' => ['class' => 'col-sm-6 col-md-3'],
            'content' => [
                'header' => 'Logs',
                'body' => '<h3>' . $total_logs . '</h3>',
            ]
        ],
    ];
@endphp

@section('header')
    <section class="container-fluid">
        <h1>
            <span class="text-capitalize">Dashboard</span>
            <small>Overview of your data.</small>
        </h1>
    </section>
@endsection

@section('content')
    <div class="row">
        <!-- Stats Cards -->
        <div class="col-sm-6 col-md-3 mb-4">
            <div class="card card-sm bg-primary-lt">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-primary text-white avatar"><i class="la la-plug"></i></span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">Integrations</div>
                            <div class="text-muted">{{ $total_integrations }} total</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3 mb-4">
            <div class="card card-sm bg-success-lt">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-success text-white avatar"><i class="la la-shopping-cart"></i></span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">Products</div>
                            <div class="text-muted">{{ $total_products }} total</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3 mb-4">
            <div class="card card-sm bg-info-lt">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-info text-white avatar"><i class="la la-tags"></i></span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">Categories</div>
                            <div class="text-muted">{{ $total_categories }} total</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3 mb-4">
            <div class="card card-sm bg-warning-lt">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-warning text-white avatar"><i class="la la-history"></i></span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">Logs</div>
                            <div class="text-muted">{{ $total_logs }} total</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts and Activity -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Products per Integration</h3>
                </div>
                <div class="card-body">
                    <canvas id="productsChart" height="250"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recent Activity</h3>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($recent_logs as $log)
                            <div class="list-group-item">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <span class="status-dot status-{{ $log->type == 'error' ? 'red' : 'green' }}"></span>
                                    </div>
                                    <div class="col text-truncate">
                                        <div class="text-reset d-block">{{ $log->message }}</div>
                                        <div class="d-block text-muted text-truncate mt-n1">
                                            {{ $log->integration->name ?? 'System' }} • {{ $log->created_at->diffForHumans() }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="p-3 text-center text-muted">No recent activity</div>
                        @endforelse
                    </div>
                </div>
                <div class="card-footer text-center">
                    <a href="{{ backpack_url('log') }}" class="btn btn-link">View all logs</a>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('after_scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('productsChart').getContext('2d');
        const labels = @json($integrations_stats->pluck('name'));
        const counts = @json($integrations_stats->pluck('count'));

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: '# of Products',
                    data: counts,
                    backgroundColor: 'rgba(32, 107, 196, 0.2)',
                    borderColor: 'rgba(32, 107, 196, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
@endpush

@push('after_styles')
    <style>
        .card-sm .avatar {
            width: 2.5rem;
            height: 2.5rem;
            line-height: 2.5rem;
            font-size: 1.25rem;
        }
    </style>
@endpush
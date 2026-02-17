@php
    $search = request()->input('search');
    $query = App\Models\Product::where('integration_id', $entry->getKey());

    if ($search) {
        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhere('id', 'like', "%{$search}%");
        });
    }

    $products = $query->orderBy('created_at', 'desc')->paginate(25);
@endphp

<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h3 class="card-title">Products ({{ $products->total() }})</h3>

            <form method="GET" class="form-inline m-0">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Search products..."
                        value="{{ $search }}">
                    <button class="btn btn-outline-primary" type="submit">
                        <i class="la la-search"></i>
                    </button>
                    @if($search)
                        <a href="{{ url()->current() }}" class="btn btn-outline-secondary">
                            <i class="la la-times"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped card-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>SKU</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td>{{ $product->id }}</td>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->sku }}</td>
                            <td>{{ $product->price }} {{ $product->currency }}</td>
                            <td>{{ $product->quantity }}</td>
                            <td>{{ $product->updated_at->diffForHumans() }}</td>
                            <td>
                                <a href="#" onclick="showProductDetails({{ $product->auto_id }})"
                                    class="btn btn-sm btn-link">
                                    <i class="la la-eye"></i> Details
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">No products found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card-footer d-flex justify-content-center">
        {{ $products->appends(request()->query())->fragment('tab_products')->links() }}
    </div>
</div>

<style>
    .card-header h3.card-title {
        margin-bottom: 0;
    }
</style>

@push('after_scripts')
    <script>
        $(document).ready(function () {
            // Activate tab from hash
            if (window.location.hash) {
                let tabEl = document.querySelector(`a[data-bs-target="${window.location.hash}"], a[href="${window.location.hash}"]`);
                if (tabEl) {
                    if (typeof bootstrap !== 'undefined' && bootstrap.Tab) {
                        bootstrap.Tab.getOrCreateInstance(tabEl).show();
                    } else {
                        $(tabEl).tab('show');
                    }
                }
            }

            // Update hash on tab click
            $('a[data-bs-toggle="tab"], a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                let hash = e.target.getAttribute('href') || e.target.getAttribute('data-bs-target');
                if (hash && hash.startsWith('#')) {
                    history.replaceState(null, null, hash);
                }
            });
        });

        function showProductDetails(productAutoId) {
            // You can implement a modal or redirect to show product details
            alert('Show details for product ' + productAutoId);
        }
    </script>
@endpush

<!-- Product Details Modal -->
<div class="modal fade" id="productModal" tabindex="-1" role="dialog" aria-labelledby="productModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productModalLabel">Product Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Product Details -->
                    <div class="col-md-6">
                        <div id="productDetails"></div>
                    </div>
                    <!-- Product History -->
                    <div class="col-md-6">
                        <div id="productHistory">
                            <h5>Price History</h5>
                            <canvas id="priceChart" height="200"></canvas>
                            <h5 class="mt-4">Quantity History</h5>
                            <canvas id="quantityChart" height="200"></canvas>
                            <h5 class="mt-4">Recent Changes</h5>
                            <div id="recentChanges"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('after_styles')
    <style>
        .product-detail-row {
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .product-detail-label {
            font-weight: bold;
            opacity: 0.8;
        }

        .history-item {
            padding: 8px;
            margin-bottom: 8px;
            border-left: 3px solid #4CAF50;
            background: rgba(0, 0, 0, 0.03);
        }

        .json-preview {
            background: rgba(0, 0, 0, 0.05);
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            white-space: pre-wrap;
        }

        /* Dark mode specific adjustments if needed */
        [data-bs-theme="dark"] .product-detail-row {
            border-bottom-color: rgba(255, 255, 255, 0.1);
        }

        [data-bs-theme="dark"] .history-item,
        [data-bs-theme="dark"] .json-preview {
            background: rgba(255, 255, 255, 0.05);
        }
    </style>
@endpush

@push('after_scripts')

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let priceChart = null;
        let quantityChart = null;

        function showProductDetails(productAutoId) {
            $('#productModal').modal('show');
            $('#productDetails').html('<div class="text-center"><i class="la la-spinner la-spin"></i> Loading...</div>');
            $('#recentChanges').html('<div class="text-center"><i class="la la-spinner la-spin"></i> Loading...</div>');

            // Fetch product details
            fetch(`/admin/product/${productAutoId}`)
                .then(response => response.json())
                .then(data => {
                    let html = '';

                    // Basic Details
                    html += createDetailRow('Name', data.name);
                    html += createDetailRow('SKU', data.sku);
                    html += createDetailRow('EAN', data.ean);
                    html += createDetailRow('Price', `${data.price} ${data.currency}`);
                    html += createDetailRow('Quantity', data.quantity);
                    html += createDetailRow('Tax', `${data.tax}%`);

                    // Dimensions
                    html += createDetailRow('Weight', `${data.weight} kg`);
                    html += createDetailRow('Dimensions', `${data.length}x${data.width}x${data.height} cm`);

                    // Descriptions
                    html += createDetailRow('Description', data.description);
                    if (data.description_extra1) html += createDetailRow('Extra Description 1', data.description_extra1);
                    if (data.description_extra2) html += createDetailRow('Extra Description 2', data.description_extra2);
                    if (data.description_extra3) html += createDetailRow('Extra Description 3', data.description_extra3);
                    if (data.description_extra4) html += createDetailRow('Extra Description 4', data.description_extra4);

                    // Additional Info
                    html += createDetailRow('Manufacturer', data.man_name);
                    html += createDetailRow('Location', data.location);
                    html += createDetailRow('URL', `<a href="${data.url}" target="_blank">${data.url}</a>`);

                    // Arrays
                    if (data.images?.length) {
                        html += createDetailRow('Images', formatJsonArray(data.images));
                    }
                    if (data.features?.length) {
                        html += createDetailRow('Features', formatJsonArray(data.features));
                    }
                    if (data.variants?.length) {
                        html += createDetailRow('Variants', formatJsonArray(data.variants));
                    }

                    $('#productDetails').html(html);
                });

            // Fetch history data
            fetch(`/admin/product-history/${productAutoId}`)
                .then(response => response.json())
                .then(data => {
                    updateCharts(data);
                    displayRecentChanges(data);
                });
        }

        function createDetailRow(label, value) {
            return `
                    <div class="product-detail-row">
                        <div class="product-detail-label">${label}</div>
                        <div class="product-detail-value">${value || '-'}</div>
                    </div>
                `;
        }

        function formatJsonArray(data) {
            return `<div class="json-preview">${JSON.stringify(data, null, 2)}</div>`;
        }

        function updateCharts(historyData) {
            const priceData = historyData.filter(item => item.field_name.toLowerCase() === 'price');
            const quantityData = historyData.filter(item => item.field_name.toLowerCase() === 'quantity');

            // Destroy existing charts
            if (priceChart) priceChart.destroy();
            if (quantityChart) quantityChart.destroy();

            // Create price chart
            priceChart = createChart('priceChart', priceData, 'Price History');
            quantityChart = createChart('quantityChart', quantityData, 'Quantity History');
        }

        function createChart(canvasId, data, label) {
            const ctx = document.getElementById(canvasId).getContext('2d');
            return new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map(item => new Date(item.created_at).toLocaleDateString()),
                    datasets: [{
                        label: label,
                        data: data.map(item => item.new_value),
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: false
                        }
                    }
                }
            });
        }

        function displayRecentChanges(data) {
            let html = '';
            data.slice(0, 10).forEach(item => {
                html += `
                        <div class="history-item">
                            <div><strong>${item.field_name}</strong> ${item.variant_id ? `(Variant: ${item.variant_id})` : ''}</div>
                            <div>Changed from ${item.old_value} to ${item.new_value}</div>
                            <small class="text-muted">${new Date(item.created_at).toLocaleString()}</small>
                        </div>
                    `;
            });
            $('#recentChanges').html(html || '<div class="text-center">No recent changes</div>');
        }
    </script>
@endpush
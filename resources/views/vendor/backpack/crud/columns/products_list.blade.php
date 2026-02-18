@php
    $search = request()->input('search');
    $categoryFilter = request()->input('category');
    $sort = request()->input('sort', 'created_at');
    $order = request()->input('order', 'desc');

    $query = App\Models\Product::where('integration_id', $entry->getKey());

    if ($search) {
        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhere('id', 'like', "%{$search}%");
        });
    }

    if ($categoryFilter) {
        $query->whereHas('categories', function ($q) use ($categoryFilter) {
            $q->where('categories.auto_id', $categoryFilter);
        });
    }

    $products = $query->orderBy($sort, $order)->paginate(25);

    $categories = \App\Models\Category::where('integration_id', $entry->getKey())
        ->orderBy('name')
        ->get();

    if (!function_exists('getSortUrl')) {
        function getSortUrl($field, $currentSort, $currentOrder)
        {
            $newOrder = ($field === $currentSort && $currentOrder === 'asc') ? 'desc' : 'asc';
            return url()->current() . '?' . http_build_query(array_merge(request()->query(), [
                'sort' => $field,
                'order' => $newOrder
            ])) . '#tab_products';
        }
    }

    if (!function_exists('getSortIcon')) {
        function getSortIcon($field, $currentSort, $currentOrder)
        {
            if ($field !== $currentSort)
                return 'la-sort';
            return $currentOrder === 'asc' ? 'la-sort-up' : 'la-sort-down';
        }
    }
@endphp

<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h3 class="card-title">Products ({{ $products->total() }})</h3>

            <form method="GET" class="form-inline m-0">
                <div class="input-group">
                    <select name="category" class="form-select" onchange="this.form.submit()" style="max-width: 200px;">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->auto_id }}" {{ $categoryFilter == $category->auto_id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    <input type="text" name="search" class="form-control" placeholder="Search products..."
                        value="{{ $search }}">
                    <button class="btn btn-outline-primary" type="submit">
                        <i class="la la-search"></i>
                    </button>
                    @if($search || $categoryFilter)
                        <a href="{{ url()->current() }}#tab_products" class="btn btn-outline-secondary">
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
                        <th><a href="{{ getSortUrl('id', $sort, $order) }}">ID <i
                                    class="la {{ getSortIcon('id', $sort, $order) }}"></i></a></th>
                        <th><a href="{{ getSortUrl('name', $sort, $order) }}">Name <i
                                    class="la {{ getSortIcon('name', $sort, $order) }}"></i></a></th>
                        <th><a href="{{ getSortUrl('sku', $sort, $order) }}">SKU <i
                                    class="la {{ getSortIcon('sku', $sort, $order) }}"></i></a></th>
                        <th><a href="{{ getSortUrl('price', $sort, $order) }}">Price <i
                                    class="la {{ getSortIcon('price', $sort, $order) }}"></i></a></th>
                        <th><a href="{{ getSortUrl('quantity', $sort, $order) }}">Quantity <i
                                    class="la {{ getSortIcon('quantity', $sort, $order) }}"></i></a></th>
                        <th><a href="{{ getSortUrl('updated_at', $sort, $order) }}">Updated <i
                                    class="la {{ getSortIcon('updated_at', $sort, $order) }}"></i></a></th>
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

    .card-table thead th a {
        color: inherit;
        text-decoration: none;
        display: block;
        width: 100%;
    }

    .card-table thead th a:hover {
        opacity: 0.7;
    }

    .card-table thead th i {
        font-size: 0.8rem;
        margin-left: 2px;
        opacity: 0.5;
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
            // Ensure modal is at the end of body to prevent backdrop issues
            if ($('#productModal').parent().is('body') === false) {
                $('#productModal').appendTo('body');
            }

            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('productModal'));
            modal.show();

            $('#productDetails').html('<div class="text-center p-4"><i class="la la-spinner la-spin la-3x"></i><br>Loading details...</div>');
            $('#recentChanges').html('<div class="text-center p-4"><i class="la la-spinner la-spin la-3x"></i><br>Loading history...</div>');

            // Fetch product details
            fetch(`/admin/product/${productAutoId}`)
                .then(response => response.json())
                .then(data => {
                    let html = '';
                    html += createDetailRow('Name', data.name);
                    html += createDetailRow('SKU', data.sku);
                    html += createDetailRow('EAN', data.ean);
                    html += createDetailRow('Price', `${data.price} ${data.currency}`);
                    html += createDetailRow('Quantity', data.quantity);
                    html += createDetailRow('Tax', `${data.tax}%`);
                    html += createDetailRow('Weight', `${data.weight} kg`);
                    html += createDetailRow('Dimensions', `${data.length}x${data.width}x${data.height} cm`);
                    html += createDetailRow('Description', data.description);
                    if (data.description_extra1) html += createDetailRow('Extra Description 1', data.description_extra1);
                    if (data.description_extra2) html += createDetailRow('Extra Description 2', data.description_extra2);
                    if (data.description_extra3) html += createDetailRow('Extra Description 3', data.description_extra3);
                    if (data.description_extra4) html += createDetailRow('Extra Description 4', data.description_extra4);
                    html += createDetailRow('Manufacturer', data.man_name);
                    html += createDetailRow('Location', data.location);
                    html += createDetailRow('URL', `<a href="${data.url}" target="_blank">${data.url}</a>`);

                    if (data.images?.length) html += createDetailRow('Images', formatImages(data.images));
                    if (data.features?.length) html += createDetailRow('Features', formatFeatures(data.features));
                    if (data.variants?.length) html += createDetailRow('Variants', formatJsonArray(data.variants));

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
    </script>
@endpush

<!-- Product Details Modal -->
<div class="modal fade" id="productModal" tabindex="-1" role="dialog" aria-labelledby="productModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productModalLabel">Product Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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

        function formatImages(images) {
            if (!images || !images.length) return '-';
            let html = '<div class="product-images-grid d-flex flex-wrap gap-2 mt-2">';
            images.forEach(url => {
                html += `
                            <a href="${url}" target="_blank" class="d-block border rounded overflow-hidden" style="width: 80px; height: 80px;">
                                <img src="${url}" class="w-100 h-100 object-fit-cover" onerror="this.src='https://placehold.co/80x80?text=Error'">
                            </a>
                        `;
            });
            html += '</div>';
            return html;
        }

        function formatFeatures(features) {
            if (!features || !features.length) return '-';
            let html = '<div class="features-list mt-2 border rounded overflow-hidden">';
            html += '<table class="table table-sm table-striped mb-0">';
            features.forEach(feature => {
                if (Array.isArray(feature) && feature.length >= 2) {
                    const label = feature[0];
                    const value = feature[1];
                    if (label || value) {
                        html += `
                                    <tr>
                                        <td class="fw-bold bg-light" style="width: 40%; font-size: 0.85rem;">${label}</td>
                                        <td style="font-size: 0.85rem;">${value || '-'}</td>
                                    </tr>
                                `;
                    }
                }
            });
            html += '</table></div>';
            return html;
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
@php
    $baseUrl = url('/api/integration/' . $entry->getKey());
    $methods = [
        'SupportedMethods',
        'FileVersion',
        'ProductsCategories',
        'ProductsList',
        'ProductsData',
        'ProductsPrices',
        'ProductsQuantity'
    ];
@endphp

<div class="api-urls">
        <div class="mb-1">
            <a href="{{ $baseUrl }}" target="_blank" class="small">
                {{ $baseUrl }}
            </a>
        </div>
</div>

<div class="card">
    <div class="card-body">
        <h5 class="card-title">{{ $product->name }}</h5>
        <dl class="row">
            <dt class="col-sm-3">Integration</dt>
            <dd class="col-sm-9">{{ $integration->name }}</dd>

            <dt class="col-sm-3">Product ID</dt>
            <dd class="col-sm-9">{{ $product->id }}</dd>

            <dt class="col-sm-3">SKU</dt>
            <dd class="col-sm-9">{{ $product->sku }}</dd>

            <dt class="col-sm-3">Current Price</dt>
            <dd class="col-sm-9">{{ number_format($product->price, 2) }}</dd>

            <dt class="col-sm-3">Current Quantity</dt>
            <dd class="col-sm-9">{{ $product->quantity }}</dd>

            @if($product->variants)
                <dt class="col-sm-3">Variants</dt>
                <dd class="col-sm-9">
                    <pre class="mb-0">{{ json_encode(json_decode($product->variants), JSON_PRETTY_PRINT) }}</pre>
                </dd>
            @endif
        </dl>
    </div>
</div>

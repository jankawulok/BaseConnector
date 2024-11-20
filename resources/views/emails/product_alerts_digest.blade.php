<h2>Product Alert Digest for {{ $integration->name }}</h2>
<p>The following changes were detected:</p>

<table>
    <thead>
        <tr>
            <th>Product</th>
            <th>Change</th>
            <th>Old Value</th>
            <th>New Value</th>
            <th>Time</th>
        </tr>
    </thead>
    <tbody>
        @foreach($changes as $change)
            <tr>
                <td>{{ $change->product->name }} ({{ $change->product->sku }})</td>
                <td>{{ ucfirst($change->field_name) }}</td>
                <td>{{ $change->old_value }}</td>
                <td>{{ $change->new_value }}</td>
                <td>{{ $change->created_at->format('Y-m-d H:i:s') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            Alerts Configuration
            <a href="{{ backpack_url('alert/create?integration_id='.$entry->id) }}" class="btn btn-sm btn-primary float-right">
                <i class="la la-plus"></i> Add Alert
            </a>
        </h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            @php
                $alerts = $entry->alerts()->latest()->get();
            @endphp

            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Condition</th>
                        <th>Filters</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($alerts as $alert)
                        <tr>
                            <td>{{ ucfirst(str_replace('_', ' ', $alert->type)) }}</td>
                            <td>
                                @if($alert->type === 'price_change')
                                    {{ $alert->condition['percentage'] ?? 0 }}% change
                                @elseif($alert->type === 'stock_change')
                                    {{ $alert->condition['threshold'] ?? 0 }} units change
                                @endif
                            </td>
                            <td>
                                @if(!empty($alert->filters))
                                    <small>
                                        @foreach($alert->filters as $key => $value)
                                            <div>{{ ucfirst(str_replace('_', ' ', $key)) }}: {{ $value }}</div>
                                        @endforeach
                                    </small>
                                @else
                                    <span class="text-muted">No filters</span>
                                @endif
                            </td>
                            <td>{{ $alert->notification_email }}</td>
                            <td>
                                <span class="badge badge-{{ $alert->is_active ? 'success' : 'danger' }}">
                                    {{ $alert->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ backpack_url('alert/'.$alert->id.'/edit') }}" class="btn btn-sm btn-link">
                                    <i class="la la-edit"></i> Edit
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No alerts configured</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

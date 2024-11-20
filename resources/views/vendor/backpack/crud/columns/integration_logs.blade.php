<div class="card">
    <div class="card-header">
        <h3 class="card-title">Integration Logs</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            @php
                $logs = $entry->logs()->latest()->paginate(25);
            @endphp

            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Level</th>
                        <th>Message</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                            <td>
                                <span class="badge badge-{{ $log->level === 'error' ? 'danger' : ($log->level === 'warning' ? 'warning' : 'info') }}">
                                    {{ ucfirst($log->level) }}
                                </span>
                            </td>
                            <td>{{ $log->message }}</td>
                            <td>
                                @if($log->context)
                                    <pre class="small mb-0">{{ json_encode($log->context, JSON_PRETTY_PRINT) }}</pre>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center">No logs found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="p-3">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
</div>

@if ($crud->hasAccess('update'))
    <a href="javascript:void(0)"
       onclick="syncLight(this)"
       data-route="{{ url($crud->route.'/'.$entry->getKey().'/sync-light') }}"
       class="btn btn-sm btn-link"
       data-button-type="sync_light"
       title="Light sync - updates only prices and stock levels">
        <i class="la la-bolt"></i> Light Sync
        @if($entry->last_light_sync)
            <small class="d-none d-sm-inline">
                ({{ $entry->last_light_sync->diffForHumans() }})
            </small>
        @endif
    </a>
@endif

@push('after_scripts')
<script>
    if (typeof syncLight !== 'function') {
        function syncLight(button) {
            // Check if button is disabled
            if ($(button).hasClass('disabled')) return;

            if (confirm("Are you sure you want to start a light sync? This will update prices and stock levels.")) {
                // Disable button to prevent double-clicks
                $(button).addClass('disabled');
                $(button).find('i').removeClass('la-bolt').addClass('la-spinner la-spin');

                // Redirect to sync route
                window.location = $(button).data('route');
            }
        }
    }
</script>
@endpush

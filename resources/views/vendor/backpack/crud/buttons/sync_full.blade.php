@if ($crud->hasAccess('update'))
	<a href="javascript:void(0)" onclick="syncFull(this)" data-route="{{ url($crud->route.'/'.$entry->getKey().'/sync-full') }}" class="btn btn-sm btn-link" data-button-type="sync_full">
        <i class="la la-sync"></i> Full Sync
    </a>
@endif

@push('after_scripts')
<script>
    function syncFull(button) {
        if (confirm("Are you sure you want to start a full sync?")) {
            window.location = $(button).data('route');
        }
    }
</script>
@endpush

@if ($crud->hasAccess('update'))
    <a href="javascript:void(0)" onclick="syncFull(this)"
        data-route="{{ url($crud->route . '/' . $entry->getKey() . '/sync-full') }}" class="btn btn-sm btn-link"
        data-button-type="sync_full">
        <i class="la la-sync"></i> Full Sync
    </a>
@endif
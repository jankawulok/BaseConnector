@if ($crud->hasAccess('update'))
    <a href="javascript:void(0)" onclick="syncLight(this)"
        data-route="{{ url($crud->route . '/' . $entry->getKey() . '/sync-light') }}" class="btn btn-sm btn-link"
        data-button-type="sync_light" title="Light sync - updates only prices and stock levels">
        <i class="la la-bolt"></i> Light Sync
        @if($entry->last_light_sync)
            <small class="d-none d-sm-inline">
                ({{ $entry->last_light_sync->diffForHumans() }})
            </small>
        @endif
    </a>
@endif
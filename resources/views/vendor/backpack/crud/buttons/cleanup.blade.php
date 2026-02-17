@if ($crud->hasAccess('delete'))
    <a href="javascript:void(0)" onclick="cleanup(this)"
        data-route="{{ url($crud->route . '/' . $entry->getKey() . '/cleanup') }}" class="btn btn-sm btn-link text-danger"
        data-button-type="cleanup">
        <i class="la la-trash"></i> Cleanup Products
    </a>
@endif
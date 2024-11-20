@if ($crud->hasAccess('list'))
    <a href="{{ url($crud->route.'/export') }}" class="btn btn-primary" data-style="zoom-in">
        <span class="ladda-label"><i class="la la-download"></i> Export Logs</span>
    </a>
@endif

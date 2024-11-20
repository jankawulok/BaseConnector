<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ backpack_theme_config('html_direction') }}">
<head>
    @include(backpack_view('inc.head'))
</head>

<body class="{{ backpack_theme_config('classes.body') }}">
    @include(backpack_view('inc.sidebar'))
    <main class="main pt-2">
        @include(backpack_view('inc.topbar'))
        <div class="container-fluid animated fadeIn">
            <h2>
                <span class="text-capitalize">{{ $crud->entity_name_plural }}</span>
            </h2>

            {{-- Filters --}}
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" id="filter-form" class="form-inline">
                        <div class="row w-100">
                            <div class="col-md-3 mb-2">
                                <select name="integration_id" class="form-control w-100">
                                    <option value="">All Integrations</option>
                                    @foreach(App\Models\Integration::pluck('name', 'id') as $id => $name)
                                        <option value="{{ $id }}" {{ request('integration_id') == $id ? 'selected' : '' }}>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2 mb-2">
                                <select name="level" class="form-control w-100">
                                    <option value="">All Levels</option>
                                    @foreach(['error' => 'Error', 'warning' => 'Warning', 'info' => 'Info'] as $value => $label)
                                        <option value="{{ $value }}" {{ request('level') == $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2 mb-2">
                                <input type="date" name="date_from" class="form-control w-100"
                                       value="{{ request('date_from') }}" placeholder="Date From">
                            </div>

                            <div class="col-md-2 mb-2">
                                <input type="date" name="date_to" class="form-control w-100"
                                       value="{{ request('date_to') }}" placeholder="Date To">
                            </div>

                            <div class="col-md-2 mb-2">
                                <input type="text" name="search" class="form-control w-100"
                                       value="{{ request('search') }}" placeholder="Search...">
                            </div>

                            <div class="col-md-1 mb-2">
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Table --}}
            <div class="card">
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Integration</th>
                                <th>Level</th>
                                <th>Message</th>
                                <th>Context</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($crud->entries as $entry)
                            <tr>
                                <td>{{ $entry->created_at->format('Y-m-d H:i:s') }}</td>
                                <td>{{ $entry->integration->name }}</td>
                                <td>
                                    @php
                                        $colors = [
                                            'error' => 'danger',
                                            'warning' => 'warning',
                                            'info' => 'info'
                                        ];
                                        $color = $colors[$entry->level] ?? 'secondary';
                                    @endphp
                                    <span class="badge badge-{{ $color }}">{{ $entry->level }}</span>
                                </td>
                                <td>{{ $entry->message }}</td>
                                <td>
                                    @if(!empty($entry->context))
                                        <pre class="small mb-0">{{ json_encode($entry->context, JSON_PRETTY_PRINT) }}</pre>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Pagination --}}
            @if ($crud->entries->hasPages())
                <div class="mt-3">
                    {{ $crud->entries->appends(request()->except('page'))->links() }}
                </div>
            @endif
        </div>
    </main>

    @include(backpack_view('inc.footer'))
    @include(backpack_view('inc.scripts'))
</body>
</html>

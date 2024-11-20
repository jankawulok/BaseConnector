<?php

namespace App\Http\Controllers\Admin;

use App\Models\Log;
use App\Models\Integration;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;

class LogCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        CRUD::setModel(Log::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/log');
        CRUD::setEntityNameStrings('log', 'logs');
    }

    protected function setupListOperation()
    {
        // Get filter values from request
        $request = request();
        $query = $this->crud->query;

        // Apply filters
        if ($request->filled('integration_id')) {
            $query->where('integration_id', $request->input('integration_id'));
        }
        if ($request->filled('level')) {
            $query->where('level', $request->input('level'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }
        if ($request->filled('search')) {
            $search = $request->input('search');
            if (is_array($search)) {
                $search = $search['value'];
            }
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('message', 'like', "%{$search}%")
                      ->orWhere('context', 'like', "%{$search}%");
                });
            }
        }

        // Define columns
        CRUD::column('created_at')
            ->type('datetime')
            ->format('YYYY-MM-DD HH:mm:ss');

        CRUD::column('integration')
            ->type('relationship')
            ->attribute('name');

        CRUD::column('level')
            ->type('custom_html')
            ->value(function($entry) {
                $colors = [
                    'error' => 'danger',
                    'warning' => 'warning',
                    'info' => 'info'
                ];
                $color = $colors[$entry->level] ?? 'secondary';
                return "<span class='badge badge-{$color}'>{$entry->level}</span>";
            });

        CRUD::column('message');

        CRUD::column('context')
            ->type('custom_html')
            ->value(function($entry) {
                if (empty($entry->context)) return '';
                return '<pre class="small mb-0">' . json_encode($entry->context, JSON_PRETTY_PRINT) . '</pre>';
            });

        // Add export button
        $this->crud->button('export')->stack('top')->view('vendor.backpack.crud.buttons.export');
    }

    protected function setupShowOperation()
    {
        $this->setupListOperation();
    }

    public function export(Request $request)
    {
        $query = $this->crud->query;

        // Apply the same filters as the list view
        if ($request->filled('integration_id')) {
            $query->where('integration_id', $request->input('integration_id'));
        }
        if ($request->filled('level')) {
            $query->where('level', $request->input('level'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }
        if ($request->filled('search')) {
            $search = $request->input('search');
            if (is_array($search)) {
                $search = $search['value'];
            }
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('message', 'like', "%{$search}%")
                      ->orWhere('context', 'like', "%{$search}%");
                });
            }
        }

        $logs = $query->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="logs.csv"',
        ];

        $callback = function() use ($logs) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Date', 'Integration', 'Level', 'Message', 'Context']);

            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->created_at,
                    $log->integration->name,
                    $log->level,
                    $log->message,
                    json_encode($log->context)
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

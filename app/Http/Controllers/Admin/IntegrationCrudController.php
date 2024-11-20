<?php

namespace App\Http\Controllers\Admin;

use App\Models\Integration;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Jobs\ImportIntegrationFeed;
use App\Models\Product;
use Alert;

class IntegrationCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        CRUD::setModel(Integration::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/integration');
        CRUD::setEntityNameStrings('integration', 'integrations');

        // Add custom buttons
        $this->crud->addButtonFromView('line', 'sync_full', 'sync_full', 'beginning');
        $this->crud->addButtonFromView('line', 'sync_light', 'sync_light', 'beginning');
        $this->crud->addButtonFromView('line', 'cleanup', 'cleanup', 'beginning');
    }

    /**
     * Sync full feed
     */
    public function syncFull($id)
    {
        $integration = Integration::findOrFail($id);
        ImportIntegrationFeed::dispatch($integration, 'full');

        \Prologue\Alerts\Facades\Alert::success('Full sync job has been queued.')->flash();
        return redirect()->back();
    }

    /**
     * Sync light feed
     */
    public function syncLight($id)
    {
        $integration = Integration::findOrFail($id);
        ImportIntegrationFeed::dispatch($integration, 'light');

        \Prologue\Alerts\Facades\Alert::success('Light sync job has been queued.')->flash();
        return redirect()->back();
    }

    /**
     * Cleanup all products
     */
    public function cleanup($id)
    {
        $integration = Integration::findOrFail($id);
        $count = Product::where('integration_id', $id)->delete();

        \Prologue\Alerts\Facades\Alert::success($count . ' products have been deleted.')->flash();
        return redirect()->back();
    }

    protected function setupListOperation()
    {
        CRUD::column('name');
        CRUD::column('enabled')->type('boolean');
        CRUD::column('products_count')
            ->type('closure')
            ->function(function($entry) {
                return $entry->products()->count();
            })
            ->label('Products');
        CRUD::column('api_key');
        CRUD::column('full_feed_url')->type('url');
        CRUD::column('light_feed_url')->type('url');
        CRUD::column('api_url')
            ->type('view')
            ->view('vendor.backpack.crud.columns.api_url');
        CRUD::column('created_at');
        CRUD::column('updated_at');
        CRUD::column('full_sync_schedule');
        CRUD::column('light_sync_schedule');
        CRUD::column('last_full_sync');
        CRUD::column('last_light_sync');
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation([
            'name' => 'required|min:2|max:255',
            'enabled' => 'boolean',
            'api_key' => 'nullable|min:8|max:255',
            'full_feed_url' => 'nullable|url',
            'light_feed_url' => 'nullable|url',
            'full_import_definition' => 'nullable|json',
            'light_import_definition' => 'nullable|json',
            'full_sync_schedule' => 'nullable|string',
            'light_sync_schedule' => 'nullable|string',
        ]);

        CRUD::field('name');
        CRUD::field('enabled')->type('boolean');
        CRUD::field('api_key')->hint('Optional');
        CRUD::field('full_feed_url')
            ->type('url');
        CRUD::field('light_feed_url')
            ->type('url')
            ->hint('Optional');

        // Add JSON fields with our custom editor
        CRUD::addField([
            'name' => 'full_import_definition',
            'label' => 'Full Import Definition',
            'type' => 'json_editor',
            'default' => '{}',
            'hint' => 'JSON configuration for full import paths'
        ]);

        CRUD::addField([
            'name' => 'light_import_definition',
            'label' => 'Light Import Definition',
            'type' => 'json_editor',
            'default' => '{}',
            'hint' => 'JSON configuration for light import paths'
        ]);

        CRUD::field('full_sync_schedule')
            ->type('text')
            ->label('Full Sync Schedule (Cron Expression)')
            ->default('0 0 * * *')
            ->hint('e.g., "0 0 * * *" for daily at midnight');
        CRUD::field('light_sync_schedule')
            ->type('text')
            ->label('Light Sync Schedule (Cron Expression)')
            ->default('*/30 * * * *')
            ->hint('e.g., "*/30 * * * *" for every 30 minutes');
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    protected function setupShowOperation()
    {
        $this->setupListOperation();

        // Add Products list
        CRUD::addColumn([
            'name' => 'products',
            'type' => 'view',
            'view' => 'vendor.backpack.crud.columns.products_list'
        ]);

        // Add logs
        CRUD::addColumn([
            'name' => 'logs',
            'type' => 'view',
            'view' => 'vendor.backpack.crud.columns.integration_logs'
        ]);

        // Add alerts
        CRUD::addColumn([
            'name' => 'alerts',
            'type' => 'view',
            'view' => 'vendor.backpack.crud.columns.integration_alerts'
        ]);
    }
}

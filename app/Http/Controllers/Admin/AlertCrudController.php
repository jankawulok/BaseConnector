<?php

namespace App\Http\Controllers\Admin;

use App\Models\Alert;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class AlertCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(Alert::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/alert');
        CRUD::setEntityNameStrings('alert', 'alerts');

        // Get integration_id from query string for create operation
        if (request()->has('integration_id')) {
            $this->crud->setOperationSetting('contentClass', 'col-md-12 bold-labels', 'create');
            $this->crud->setOperationSetting('defaultValues', [
                'integration_id' => request('integration_id'),
                'is_active' => true
            ], 'create');
        }
    }

    protected function setupListOperation()
    {
        CRUD::column('integration')->type('relationship');
        CRUD::column('type');
        CRUD::column('condition')->type('json');
        CRUD::column('is_active')->type('boolean');
        CRUD::column('notification_email');
    }

    protected function setupCreateOperation()
    {
        CRUD::field([
            'name' => 'name',
            'type' => 'text',
            'label' => 'Alert Name',
            'hint' => 'A descriptive name for this alert'
        ]);

        CRUD::field([
            'name' => 'integration_id',
            'type' => 'select',
            'entity' => 'integration',
            'model' => 'App\Models\Integration',
            'attribute' => 'name',
        ]);

        CRUD::field([
            'name' => 'type',
            'type' => 'select_from_array',
            'options' => [
                'price_change' => 'Price Change',
                'stock_change' => 'Stock Change',
                'product_added' => 'Product Added',
                'product_removed' => 'Product Removed'
            ]
        ]);

        CRUD::field([
            'name' => 'condition',
            'type' => 'json_editor',
            'label' => 'Alert Conditions',
            'default' => json_encode([
                'percentage' => 10,
                'threshold' => 5
            ]),
            'hint' => 'Set percentage for price changes or threshold for stock changes'
        ]);

        CRUD::field([
            'name' => 'filters',
            'type' => 'json_editor',
            'label' => 'Product Filters',
            'default' => json_encode([
                'sku_pattern' => '',
                'min_price' => 0,
                'max_price' => 0
            ]),
            'hint' => 'Optional filters to limit which products trigger alerts'
        ]);

        CRUD::field('notification_email')->type('email');
        CRUD::field('is_active')->type('boolean')->default(true);
    }
}

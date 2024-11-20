<?php

namespace App\Http\Controllers\Admin;

use App\Models\ProductHistory;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class ProductHistoryCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        CRUD::setModel(ProductHistory::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/product-history');
        CRUD::setEntityNameStrings('product history', 'product histories');
    }

    protected function setupListOperation()
    {
        CRUD::column('product')
            ->type('custom_html')
            ->label('Product')
            ->value(function($entry) {
                $product = $entry->product;
                if (!$product) return 'Product deleted';

                return sprintf(
                    '<strong>%s</strong><br>ID: %s<br>SKU: %s',
                    $product->integration->name ?? 'Unknown Integration',
                    $product->id,
                    $product->sku
                );
            });

        CRUD::column('field_name');
        CRUD::column('old_value');
        CRUD::column('new_value');
        CRUD::column('variant_id')
            ->type('text')
            ->label('Variant');
        CRUD::column('created_at')
            ->type('datetime');
    }

    protected function setupShowOperation()
    {
        $entry = $this->crud->getCurrentEntry();

        // Product Information
        CRUD::column('product')
            ->type('custom_html')
            ->label('Product Details')
            ->value(function($entry) {
                $product = $entry->product;
                if (!$product) return 'Product deleted';

                return view('vendor.backpack.crud.columns.product_details', [
                    'product' => $product,
                    'integration' => $product->integration
                ])->render();
            });

        // Price History Graph
        CRUD::column('price_history')
            ->type('view')
            ->label('Price History')
            ->view('vendor.backpack.crud.columns.history_graph')
            ->with([
                'entry' => $entry,
                'field_name' => 'price',
                'product_auto_id' => $entry->product_auto_id
            ]);

        // Quantity History Graph
        CRUD::column('quantity_history')
            ->type('view')
            ->label('Quantity History')
            ->view('vendor.backpack.crud.columns.history_graph')
            ->with([
                'entry' => $entry,
                'field_name' => 'quantity',
                'product_auto_id' => $entry->product_auto_id
            ]);

        // All Changes Table
        CRUD::column('changes')
            ->type('view')
            ->label('All Changes')
            ->view('vendor.backpack.crud.columns.product_history_table');
    }

    public function getHistory($productAutoId)
    {
        $history = ProductHistory::where('product_auto_id', $productAutoId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($history);
    }
}

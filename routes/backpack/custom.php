<?php

use Illuminate\Support\Facades\Route;
Route::get('dashboard', [\App\Http\Controllers\Admin\IntegrationCrudController::class, 'index'])->name('admin.dashboard');

// --------------------------
// Custom Backpack Routes
// --------------------------
// This route file is loaded automatically by Backpack\CRUD.
// Routes you generate using Backpack\Generators will be placed here.
Route::group([
    'prefix' => config('backpack.base.route_prefix'),
    'middleware' => ['web', 'admin'],
    'namespace' => 'App\Http\Controllers\Admin'
], function () {
    Route::crud('integration', 'IntegrationCrudController');
    Route::crud('product-history', 'ProductHistoryCrudController');
    Route::crud('log', 'LogCrudController');

    // Add the product show route
    Route::get('product/{productAutoId}', 'ProductController@show')->name('product.show');
    Route::get('product-history/{productAutoId}', [
        'as' => 'product-history.get',
        'uses' => 'ProductHistoryCrudController@getHistory',
        'operation' => 'list'
    ]);

    // Integration actions
    Route::get('integration/{id}/sync-full', 'IntegrationCrudController@syncFull')->name('integration.sync-full');
    Route::get('integration/{id}/sync-light', 'IntegrationCrudController@syncLight')->name('integration.sync-light');
    Route::get('integration/{id}/cleanup', 'IntegrationCrudController@cleanup')->name('integration.cleanup');
});

Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),
    'namespace' => 'App\Http\Controllers\Admin',
], function () {
    Route::crud('alert', 'AlertCrudController');
});

/**
 * DO NOT ADD ANYTHING HERE.
 */

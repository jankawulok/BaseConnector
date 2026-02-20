<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IntegrationController;

Route::match(['get', 'post'], '/integration/{integrationId}', [IntegrationController::class, 'handleRequest']);
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

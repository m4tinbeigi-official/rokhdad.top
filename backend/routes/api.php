<?php

use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'rokhdad-api',
    ]);
})->name('api.health');

Route::get('/ready', function () {
    return response()->json([
        'status' => 'ready',
        'service' => 'rokhdad-api',
    ]);
})->name('api.ready');

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('/', function () {
        return response()->json([
            'name' => 'Rokhdad API',
            'version' => 'v1',
            'status' => 'ok',
        ]);
    })->name('index');
});

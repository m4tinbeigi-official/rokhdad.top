<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('/', function () {
        return response()->json([
            'name' => 'Rokhdad API',
            'version' => 'v1',
            'status' => 'ok',
        ]);
    })->name('index');
});

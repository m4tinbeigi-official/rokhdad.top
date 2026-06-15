<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\LookupController;
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

    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->name('register');
        Route::post('/login', [AuthController::class, 'login'])->name('login');

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/me', [AuthController::class, 'me'])->name('me');
            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        });
    });

    Route::get('/events', [EventController::class, 'index'])->name('events.index');
    Route::get('/events/{slug}', [EventController::class, 'show'])->name('events.show');
    Route::get('/categories', [LookupController::class, 'categories'])->name('categories.index');
    Route::get('/cities', [LookupController::class, 'cities'])->name('cities.index');
});

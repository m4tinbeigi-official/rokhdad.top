<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventRegistrationController;
use App\Http\Controllers\LookupController;
use App\Http\Controllers\OrganizerController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\TicketValidationController;
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
    Route::middleware('auth:sanctum')->post('/events/{slug}/registrations', [EventRegistrationController::class, 'store'])->name('events.registrations.store');
    Route::get('/events/{slug}', [EventController::class, 'show'])->name('events.show');
    Route::middleware('auth:sanctum')->get('/tickets/validate/{token}', [TicketValidationController::class, 'show'])->name('tickets.validate');
    Route::get('/categories', [LookupController::class, 'categories'])->name('categories.index');
    Route::get('/cities', [LookupController::class, 'cities'])->name('cities.index');
    Route::get('/organizers', [OrganizerController::class, 'index'])->name('organizers.index');
    Route::get('/organizers/{slug}', [OrganizerController::class, 'show'])->name('organizers.show');
    Route::get('/people', [PersonController::class, 'index'])->name('people.index');
    Route::get('/people/{slug}', [PersonController::class, 'show'])->name('people.show');
});

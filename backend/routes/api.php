<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventRegistrationController;
use App\Http\Controllers\LookupController;
use App\Http\Controllers\OrganizerController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\SavedEventController;
use App\Http\Controllers\TicketValidationController;
use App\Http\Controllers\UserPreferenceController;
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

    // Auth (P5-002)
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->name('register');
        Route::post('/login', [AuthController::class, 'login'])->name('login');
        Route::post('/otp/request', [AuthController::class, 'requestOtp'])->name('otp.request');
        Route::post('/otp/verify', [AuthController::class, 'verifyOtp'])->name('otp.verify');

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/me', [AuthController::class, 'me'])->name('me');
            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        });
    });

    // Events (P14-001, P14-002)
    Route::get('/events', [EventController::class, 'index'])->name('events.index');
    Route::get('/events/{slug}', [EventController::class, 'show'])->name('events.show');

    // Comments (P21-001) – public list, auth-only create/delete
    Route::get('/events/{slug}/comments', [CommentController::class, 'index'])->name('events.comments.index');
    Route::get('/events/{slug}/ratings', [RatingController::class, 'index'])->name('events.ratings.index');

    // Lookup (P14-003)
    Route::get('/categories', [LookupController::class, 'categories'])->name('categories.index');
    Route::get('/cities', [LookupController::class, 'cities'])->name('cities.index');

    // Organizers & People (P14-004)
    Route::get('/organizers', [OrganizerController::class, 'index'])->name('organizers.index');
    Route::get('/organizers/{slug}', [OrganizerController::class, 'show'])->name('organizers.show');
    Route::get('/people', [PersonController::class, 'index'])->name('people.index');
    Route::get('/people/{slug}', [PersonController::class, 'show'])->name('people.show');

    // Payment gateway callback – public GET (gateway redirects user back here)
    Route::get('/payments/callback/{gateway}', [PaymentController::class, 'callback'])->name('payments.callback');

    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function () {
        // Ticket validation (P19-003)
        Route::get('/tickets/validate/{token}', [TicketValidationController::class, 'show'])->name('tickets.validate');

        // Registration (P19-002)
        Route::post('/events/{slug}/registrations', [EventRegistrationController::class, 'store'])->name('events.registrations.store');

        // Comments auth actions (P21-001)
        Route::post('/events/{slug}/comments', [CommentController::class, 'store'])->name('events.comments.store');
        Route::delete('/comments/{id}', [CommentController::class, 'destroy'])->name('comments.destroy');

        // Ratings (P21-002)
        Route::post('/events/{slug}/ratings', [RatingController::class, 'store'])->name('events.ratings.store');
        Route::get('/events/{slug}/my-rating', [RatingController::class, 'myRating'])->name('events.my-rating');
        Route::delete('/events/{slug}/ratings', [RatingController::class, 'destroy'])->name('events.ratings.destroy');

        // Saved Events (P22-002)
        Route::post('/events/{slug}/save', [SavedEventController::class, 'store'])->name('events.save');
        Route::delete('/events/{slug}/save', [SavedEventController::class, 'destroy'])->name('events.unsave');

        // Payments (P20-002, P20-003, P20-004)
        Route::post('/registrations/{id}/pay', [PaymentController::class, 'initiate'])->name('registrations.pay');
        Route::get('/payments/{id}', [PaymentController::class, 'show'])->name('payments.show');

        // User profile & preferences (P22-001)
        Route::get('/me/preferences', [UserPreferenceController::class, 'show'])->name('me.preferences.show');
        Route::put('/me/preferences', [UserPreferenceController::class, 'update'])->name('me.preferences.update');

        // User saved events list (P22-002)
        Route::get('/me/saved-events', [SavedEventController::class, 'index'])->name('me.saved-events.index');
    });
});

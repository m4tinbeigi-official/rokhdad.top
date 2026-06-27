<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AttendeeTransferController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventRegistrationController;
use App\Http\Controllers\LookupController;
use App\Http\Controllers\OrganizerController;
use App\Http\Controllers\OrganizerDashboardController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PersonalizedHomepageController;
use App\Http\Controllers\AiSearchController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\SavedEventController;
use App\Http\Controllers\TicketValidationController;
use App\Http\Controllers\UserPreferenceController;
use App\Http\Controllers\WebhookSubscriptionController;
use App\Http\Controllers\HermesProxyController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaskManagementController;

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

    // AI Search
    Route::post('/ai/search', [AiSearchController::class, 'search'])->name('ai.search');
    // Autocomplete suggestions for AI queries
    Route::get('/ai/suggestions', [AiSearchController::class, 'suggestions'])->name('ai.suggestions');

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

        // Personalized homepage ranking (P22-003)
        Route::get('/me/personalized-events', [PersonalizedHomepageController::class, 'index'])->name('me.personalized-events.index');

        // Organizer dashboard foundation (P27-001)
        Route::get('/me/organizer-dashboard', [OrganizerDashboardController::class, 'show'])->name('me.organizer-dashboard.show');
        Route::get('/me/events/{id}/attendees/export', [AttendeeTransferController::class, 'export'])->name('me.events.attendees.export');
        Route::post('/me/events/{id}/attendees/import', [AttendeeTransferController::class, 'import'])->name('me.events.attendees.import');
        Route::get('/me/campaigns', [CampaignController::class, 'index'])->name('me.campaigns.index');
        Route::post('/me/campaigns', [CampaignController::class, 'store'])->name('me.campaigns.store');
        Route::post('/me/campaigns/{id}/simulate', [CampaignController::class, 'sendSimulation'])->name('me.campaigns.simulate');

        // Webhook subscriptions (P28-002)
        Route::get('/me/webhook-subscriptions', [WebhookSubscriptionController::class, 'index'])->name('me.webhook-subscriptions.index');
        Route::post('/me/webhook-subscriptions', [WebhookSubscriptionController::class, 'store'])->name('me.webhook-subscriptions.store');
        Route::put('/me/webhook-subscriptions/{id}', [WebhookSubscriptionController::class, 'update'])->name('me.webhook-subscriptions.update');
        Route::delete('/me/webhook-subscriptions/{id}', [WebhookSubscriptionController::class, 'destroy'])->name('me.webhook-subscriptions.destroy');

        // Hermes knowledge-graph proxy (developer/admin tooling).
        Route::prefix('hermes')->name('hermes.')->group(function () {
            Route::get('/ping', [HermesProxyController::class, 'ping'])->name('ping');
            Route::post('/search', [HermesProxyController::class, 'search'])->name('search');
            Route::post('/trace', [HermesProxyController::class, 'trace'])->name('trace');
            Route::post('/snippet', [HermesProxyController::class, 'snippet'])->name('snippet');
        });
    });

// Task management API (admin only)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/admin/tasks', [TaskManagementController::class, 'index'])->name('admin.tasks.index');
    Route::post('/admin/tasks/{id}', [TaskManagementController::class, 'action'])->name('admin.tasks.action');
});
});

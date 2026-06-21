<?php

use App\Http\Controllers\SettlementDashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('organizer')->group(function () {
    Route::get('settlement/dashboard', [SettlementDashboardController::class, 'index']);
    Route::post('settlement/request-payout', [SettlementDashboardController::class, 'requestPayout']);
    Route::get('settlement/statements', [SettlementDashboardController::class, 'statements']);
    Route::get('settlement/ledger', [SettlementDashboardController::class, 'ledger']);
});

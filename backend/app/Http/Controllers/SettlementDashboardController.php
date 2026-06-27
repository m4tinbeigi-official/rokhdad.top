<?php

namespace App\Http\Controllers;

use App\Models\Organizer;
use App\Services\SettlementService;
use Illuminate\Http\Request;

class SettlementDashboardController extends Controller
{
    public function index(Request $request)
    {
        $organizer = $request->user()->organizer;

        if (!$organizer) {
            return response()->json(['error' => 'Not an organizer'], 403);
        }

        $balance = SettlementService::calculateOrganizerBalance($organizer);
        $payouts = $organizer->payouts()->latest()->paginate(20);
        $statement = SettlementService::generateMonthlyStatement(
            $organizer, 
            now()->format('Y-m')
        );

        return response()->json([
            'balance' => $balance,
            'payouts' => $payouts,
            'statement' => $statement,
            'can_request_payout' => $balance['available'] >= 100000,
        ]);
    }

    public function requestPayout(Request $request)
    {
        $organizer = $request->user()->organizer;

        if (!$organizer) {
            return response()->json(['error' => 'Not an organizer'], 403);
        }

        $validated = $request->validate([
            'amount' => 'required|integer|min:100000',
            'bank_account' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $balance = SettlementService::calculateOrganizerBalance($organizer);

        if ($validated['amount'] > $balance['available']) {
            return response()->json([
                'error' => 'Insufficient balance',
                'available' => $balance['available']
            ], 422);
        }

        $success = SettlementService::createWithdrawalRequest(
            $organizer,
            $validated['amount'],
            [
                'bank_account' => $validated['bank_account'],
                'notes' => $validated['notes'] ?? null,
            ]
        );

        if (!$success) {
            return response()->json(['error' => 'Failed to create withdrawal'], 422);
        }

        return response()->json([
            'message' => 'Withdrawal request created',
            'amount' => $validated['amount'],
            'status' => 'pending',
        ], 201);
    }

    public function statements(Request $request)
    {
        $organizer = $request->user()->organizer;

        if (!$organizer) {
            return response()->json(['error' => 'Not an organizer'], 403);
        }

        $month = $request->input('month', now()->format('Y-m'));
        $statement = SettlementService::generateMonthlyStatement($organizer, $month);

        return response()->json(['data' => $statement]);
    }

    public function ledger(Request $request)
    {
        $organizer = $request->user()->organizer;

        if (!$organizer) {
            return response()->json(['error' => 'Not an organizer'], 403);
        }

        $ledger = \App\Models\SettlementLedger::where('organizer_id', $organizer->id)
            ->orderByDesc('created_at')
            ->paginate(50);

        return response()->json(['data' => $ledger]);
    }
}

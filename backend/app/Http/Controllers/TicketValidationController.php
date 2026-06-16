<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\JsonResponse;

class TicketValidationController extends Controller
{
    public function show(string $token): JsonResponse
    {
        $ticket = Ticket::query()
            ->with(['event', 'user', 'registration'])
            ->where('qr_code_token', $token)
            ->firstOrFail();

        return response()->json([
            'data' => [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'qr_code_token' => $ticket->qr_code_token,
                'status' => $ticket->status,
                'usable' => $ticket->isUsable(),
                'used_at' => $ticket->used_at?->toJSON(),
                'expires_at' => $ticket->expires_at?->toJSON(),
                'event' => [
                    'id' => $ticket->event->id,
                    'title' => $ticket->event->title,
                    'slug' => $ticket->event->slug,
                ],
                'registration' => [
                    'id' => $ticket->registration->id,
                    'status' => $ticket->registration->status,
                    'payment_status' => $ticket->registration->payment_status,
                ],
                'attendee' => [
                    'id' => $ticket->user->id,
                    'name' => $ticket->user->name,
                    'email' => $ticket->user->email,
                ],
            ],
        ]);
    }
}

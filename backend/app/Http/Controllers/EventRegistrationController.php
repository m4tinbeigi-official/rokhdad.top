<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventTicketType;
use App\Models\Registration;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EventRegistrationController extends Controller
{
    public function store(Request $request, string $slug): JsonResponse
    {
        $event = Event::query()
            ->with('ticketTypes')
            ->where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        $this->ensureRegistrationIsOpen($event);

        $data = $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1', 'max:10'],
            'ticket_type_id' => ['nullable', 'integer'],
            'form_data' => ['nullable', 'array'],
        ]);

        $user = $request->user();
        $quantity = (int) ($data['quantity'] ?? 1);

        if (Registration::query()->where('event_id', $event->id)->where('user_id', $user->id)->exists()) {
            throw new ConflictHttpException('User is already registered for this event.');
        }

        $ticketType = $this->resolveTicketType($event, $data['ticket_type_id'] ?? null, $quantity);
        $this->ensureEventCapacity($event, $quantity);

        $totalAmount = ($ticketType?->price ?? 0) * $quantity;

        $registration = Registration::query()->create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => $event->requires_approval ? 'pending' : 'confirmed',
            'payment_status' => $totalAmount === 0 ? 'free' : 'unpaid',
            'quantity' => $quantity,
            'total_amount' => $totalAmount,
            'currency' => $ticketType?->currency ?? 'IRR',
            'form_data' => $data['form_data'] ?? null,
            'confirmed_at' => $event->requires_approval ? null : now(),
        ]);

        if ($ticketType) {
            $ticketType->increment('sold_count', $quantity);
        }

        for ($index = 0; $index < $quantity; $index++) {
            Ticket::query()->create([
                'registration_id' => $registration->id,
                'event_id' => $event->id,
                'user_id' => $user->id,
                'status' => 'issued',
                'price' => $ticketType?->price ?? 0,
                'expires_at' => $event->ends_at,
            ]);
        }

        return response()->json([
            'data' => $this->serializeRegistration($registration->load(['event', 'user', 'tickets'])),
        ], 201);
    }

    private function ensureRegistrationIsOpen(Event $event): void
    {
        if (! $event->is_internal || ! $event->registration_open) {
            throw new NotFoundHttpException('Registration is not available for this event.');
        }

        if ($event->registration_starts_at && $event->registration_starts_at->isFuture()) {
            throw ValidationException::withMessages([
                'registration' => ['Registration has not started yet.'],
            ]);
        }

        if ($event->registration_ends_at && $event->registration_ends_at->isPast()) {
            throw ValidationException::withMessages([
                'registration' => ['Registration has ended.'],
            ]);
        }
    }

    private function resolveTicketType(Event $event, ?int $ticketTypeId, int $quantity): ?EventTicketType
    {
        if ($ticketTypeId === null) {
            return null;
        }

        /** @var EventTicketType|null $ticketType */
        $ticketType = $event->ticketTypes->firstWhere('id', $ticketTypeId);

        if (! $ticketType) {
            throw ValidationException::withMessages([
                'ticket_type_id' => ['Selected ticket type does not belong to this event.'],
            ]);
        }

        if ($quantity > $ticketType->max_per_user) {
            throw ValidationException::withMessages([
                'quantity' => ['Quantity exceeds the ticket type per-user limit.'],
            ]);
        }

        $remaining = $ticketType->remainingCapacity();
        if (! $ticketType->isAvailableForSale() || ($remaining !== null && $remaining < $quantity)) {
            throw ValidationException::withMessages([
                'ticket_type_id' => ['Selected ticket type is not available.'],
            ]);
        }

        return $ticketType;
    }

    private function ensureEventCapacity(Event $event, int $quantity): void
    {
        if ($event->capacity === null) {
            return;
        }

        $reserved = Registration::query()
            ->where('event_id', $event->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->sum('quantity');

        if (($reserved + $quantity) > $event->capacity) {
            throw ValidationException::withMessages([
                'quantity' => ['Event capacity is full.'],
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeRegistration(Registration $registration): array
    {
        return [
            'id' => $registration->id,
            'event_id' => $registration->event_id,
            'user_id' => $registration->user_id,
            'status' => $registration->status,
            'payment_status' => $registration->payment_status,
            'quantity' => $registration->quantity,
            'total_amount' => $registration->total_amount,
            'currency' => $registration->currency,
            'form_data' => $registration->form_data,
            'confirmed_at' => $registration->confirmed_at?->toJSON(),
            'tickets' => $registration->tickets->map(fn (Ticket $ticket) => [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'qr_code_token' => $ticket->qr_code_token,
                'status' => $ticket->status,
            ])->values(),
            'event' => [
                'id' => $registration->event->id,
                'title' => $registration->event->title,
                'slug' => $registration->event->slug,
            ],
        ];
    }
}

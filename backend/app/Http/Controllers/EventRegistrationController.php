<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventPromoCode;
use App\Models\EventTicketType;
use App\Models\Registration;
use App\Models\Ticket;
use App\Webhooks\WebhookDispatcher;
use App\Webhooks\WebhookEventCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EventRegistrationController extends Controller
{
    public function __construct(private WebhookDispatcher $webhooks) {}

    public function store(Request $request, string $slug): JsonResponse
    {
        $event = Event::query()
            ->with(['ticketTypes', 'promoCodes'])
            ->where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        $this->ensureRegistrationIsOpen($event);

        $data = $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1', 'max:10'],
            'ticket_type_id' => ['nullable', 'integer'],
            'promo_code' => ['nullable', 'string', 'max:64'],
            'form_data' => ['nullable', 'array'],
        ]);

        $user = $request->user();
        $quantity = (int) ($data['quantity'] ?? 1);

        if (Registration::query()->where('event_id', $event->id)->where('user_id', $user->id)->exists()) {
            throw new ConflictHttpException('User is already registered for this event.');
        }

        $ticketType = $this->resolveTicketType($event, $data['ticket_type_id'] ?? null, $quantity);
        $this->ensureQuantityRules($event, $quantity);
        $this->ensureEventCapacity($event, $quantity);
        $validatedFormData = $this->validateRegistrationFormData($event, $data['form_data'] ?? null);

        $subtotalAmount = ($ticketType?->price ?? 0) * $quantity;
        $promoCode = $this->resolvePromoCode($event, $data['promo_code'] ?? null, $quantity, $subtotalAmount);
        $discountAmount = $promoCode?->discountAmount($subtotalAmount) ?? 0;
        $totalAmount = max(0, $subtotalAmount - $discountAmount);
        $storedFormData = $validatedFormData;

        if ($promoCode || $discountAmount > 0) {
            $storedFormData ??= [];
            $storedFormData['promo_code'] = $promoCode?->code;
            $storedFormData['discount_amount'] = $discountAmount;
        }

        $registration = Registration::query()->create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => $event->requires_approval ? 'pending' : 'confirmed',
            'payment_status' => $totalAmount === 0 ? 'free' : 'unpaid',
            'quantity' => $quantity,
            'total_amount' => $totalAmount,
            'currency' => $ticketType?->currency ?? 'IRR',
            'form_data' => $storedFormData,
            'confirmed_at' => $event->requires_approval ? null : now(),
        ]);

        if ($ticketType) {
            $ticketType->increment('sold_count', $quantity);
        }
        if ($promoCode) {
            $promoCode->increment('used_count');
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

        $this->webhooks->dispatchForOrganizer(
            (int) $event->organizer_id,
            WebhookEventCatalog::REGISTRATION_CREATED,
            [
                'registration' => $this->serializeRegistration($registration->load(['event', 'user', 'tickets'])),
                'event' => [
                    'id' => $event->id,
                    'slug' => $event->slug,
                    'title' => $event->title,
                ],
            ],
        );

        if ($registration->status === 'confirmed') {
            $this->webhooks->dispatchForOrganizer(
                (int) $event->organizer_id,
                WebhookEventCatalog::REGISTRATION_CONFIRMED,
                [
                    'registration' => $this->serializeRegistration($registration->load(['event', 'user', 'tickets'])),
                    'event' => [
                        'id' => $event->id,
                        'slug' => $event->slug,
                        'title' => $event->title,
                    ],
                ],
            );
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

    private function ensureQuantityRules(Event $event, int $quantity): void
    {
        $rules = Arr::get($event->metadata, 'registration_rules', []);
        $minQuantity = isset($rules['min_quantity']) ? max(1, (int) $rules['min_quantity']) : null;
        $maxQuantity = isset($rules['max_quantity']) ? max(1, (int) $rules['max_quantity']) : null;

        if ($minQuantity !== null && $quantity < $minQuantity) {
            throw ValidationException::withMessages([
                'quantity' => ["حداقل تعداد مجاز برای این رویداد {$minQuantity} است."],
            ]);
        }

        if ($maxQuantity !== null && $quantity > $maxQuantity) {
            throw ValidationException::withMessages([
                'quantity' => ["حداکثر تعداد مجاز برای این رویداد {$maxQuantity} است."],
            ]);
        }
    }

    private function resolvePromoCode(Event $event, ?string $promoCodeValue, int $quantity, int $subtotalAmount): ?EventPromoCode
    {
        $promoCodeValue = trim((string) $promoCodeValue);

        if ($promoCodeValue === '') {
            return null;
        }

        /** @var EventPromoCode|null $promoCode */
        $promoCode = $event->promoCodes->first(fn (EventPromoCode $code) => strcasecmp($code->code, $promoCodeValue) === 0);

        if (! $promoCode || ! $promoCode->isAvailableForQuantity($quantity) || $subtotalAmount <= 0) {
            throw ValidationException::withMessages([
                'promo_code' => ['کد تخفیف معتبر نیست یا برای این تعداد قابل استفاده نیست.'],
            ]);
        }

        return $promoCode;
    }

    /**
     * @param array<string, mixed>|null $submittedData
     * @return array<string, mixed>|null
     */
    private function validateRegistrationFormData(Event $event, ?array $submittedData): ?array
    {
        $schema = Arr::get($event->metadata, 'registration_form.fields', []);

        if (! is_array($schema) || $schema === []) {
            return $submittedData;
        }

        $submittedData ??= [];
        $errors = [];
        $validated = [];

        foreach ($schema as $field) {
            if (! is_array($field)) {
                continue;
            }

            $name = trim((string) ($field['name'] ?? ''));
            $label = trim((string) ($field['label'] ?? $name));
            $type = trim((string) ($field['type'] ?? 'text'));
            $required = (bool) ($field['required'] ?? false);

            if ($name === '') {
                continue;
            }

            $value = $submittedData[$name] ?? null;

            if ($required && ($value === null || $value === '')) {
                $errors["form_data.$name"] = ["{$label} الزامی است."];
                continue;
            }

            if ($value === null || $value === '') {
                continue;
            }

            if ($type === 'select') {
                $options = collect($field['options'] ?? [])
                    ->filter(fn ($option) => is_array($option) && isset($option['value']))
                    ->pluck('value')
                    ->map(fn ($option) => (string) $option)
                    ->all();

                if (! in_array((string) $value, $options, true)) {
                    $errors["form_data.$name"] = ["{$label} نامعتبر است."];
                    continue;
                }
            }

            if ($type === 'checkbox') {
                $validated[$name] = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false;
                continue;
            }

            $stringValue = trim((string) $value);

            if (($field['max_length'] ?? null) !== null) {
                $maxLength = max(1, (int) $field['max_length']);
                if (mb_strlen($stringValue) > $maxLength) {
                    $errors["form_data.$name"] = ["{$label} نباید بیشتر از {$maxLength} کاراکتر باشد."];
                    continue;
                }
            }

            $validated[$name] = $stringValue;
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $validated === [] ? null : $validated;
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
            'subtotal_amount' => $registration->total_amount + ((int) ($registration->form_data['discount_amount'] ?? 0)),
            'discount_amount' => (int) ($registration->form_data['discount_amount'] ?? 0),
            'promo_code' => $registration->form_data['promo_code'] ?? null,
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

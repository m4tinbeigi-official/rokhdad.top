<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Registration;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AttendeeTransferController extends Controller
{
    public function export(Request $request, int $eventId): StreamedResponse
    {
        $event = $this->ownedEvent($request, $eventId);

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="attendees-'.$event->slug.'.csv"',
        ];

        return response()->streamDownload(function () use ($event) {
            $handle = fopen('php://output', 'wb');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'name',
                'email',
                'phone_e164',
                'quantity',
                'status',
                'payment_status',
                'total_amount',
                'currency',
                'form_data_json',
            ]);

            Registration::query()
                ->with('user')
                ->where('event_id', $event->id)
                ->orderBy('id')
                ->chunk(200, function ($registrations) use ($handle) {
                    foreach ($registrations as $registration) {
                        fputcsv($handle, [
                            $registration->user?->name,
                            $registration->user?->email,
                            $registration->user?->phone_e164,
                            $registration->quantity,
                            $registration->status,
                            $registration->payment_status,
                            $registration->total_amount,
                            $registration->currency,
                            json_encode($registration->form_data ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        ]);
                    }
                });

            fclose($handle);
        }, 'attendees-'.$event->slug.'.csv', $headers);
    }

    public function import(Request $request, int $eventId): JsonResponse
    {
        $event = $this->ownedEvent($request, $eventId);

        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            'default_status' => ['nullable', 'string', 'in:pending,confirmed'],
            'send_free_payment_status' => ['nullable', 'string', 'in:free,paid,unpaid'],
        ]);

        $file = $data['file'];
        $handle = fopen($file->getRealPath(), 'rb');

        if (! $handle) {
            throw new NotFoundHttpException('CSV file could not be opened.');
        }

        $header = fgetcsv($handle) ?: [];
        $normalizedHeader = array_map(fn ($value) => trim((string) $value), $header);
        $imported = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if ($row === [null] || $row === false) {
                continue;
            }

            $payload = array_combine($normalizedHeader, array_pad($row, count($normalizedHeader), null));
            if (! is_array($payload)) {
                $skipped++;
                continue;
            }

            $email = trim((string) ($payload['email'] ?? ''));
            $phone = trim((string) ($payload['phone_e164'] ?? ''));

            if ($email === '' && $phone === '') {
                $skipped++;
                continue;
            }

            DB::transaction(function () use ($payload, $event, $data, &$imported, &$skipped) {
                $quantity = max(1, (int) ($payload['quantity'] ?? 1));
                $email = trim((string) ($payload['email'] ?? ''));
                $phone = trim((string) ($payload['phone_e164'] ?? ''));
                $existingUserQuery = User::query();

                if ($email !== '') {
                    $existingUserQuery->where('email', $email);
                } elseif ($phone !== '') {
                    $existingUserQuery->where('phone_e164', $phone);
                }

                $user = $existingUserQuery->first();

                if (! $user) {
                    $user = User::query()->create([
                        'name' => trim((string) ($payload['name'] ?? 'شرکت کننده')),
                        'email' => $email !== '' ? $email : null,
                        'phone_e164' => $phone !== '' ? $phone : null,
                        'password' => Hash::make(Str::random(24)),
                        'status' => 'active',
                        'locale' => 'fa',
                        'timezone' => 'Asia/Tehran',
                    ]);
                }

                if (Registration::query()->where('event_id', $event->id)->where('user_id', $user->id)->exists()) {
                    $skipped++;
                    return;
                }

                $status = in_array(($payload['status'] ?? null), ['pending', 'confirmed'], true)
                    ? $payload['status']
                    : ($data['default_status'] ?? 'confirmed');
                $paymentStatus = in_array(($payload['payment_status'] ?? null), ['free', 'paid', 'unpaid'], true)
                    ? $payload['payment_status']
                    : ($data['send_free_payment_status'] ?? 'free');
                $totalAmount = max(0, (int) ($payload['total_amount'] ?? 0));
                $formData = json_decode((string) ($payload['form_data_json'] ?? '{}'), true);

                $registration = Registration::query()->create([
                    'event_id' => $event->id,
                    'user_id' => $user->id,
                    'status' => $status,
                    'payment_status' => $paymentStatus,
                    'quantity' => $quantity,
                    'total_amount' => $totalAmount,
                    'currency' => trim((string) ($payload['currency'] ?? 'IRR')) ?: 'IRR',
                    'form_data' => is_array($formData) ? $formData : null,
                    'confirmed_at' => $status === 'confirmed' ? now() : null,
                ]);

                for ($index = 0; $index < $quantity; $index++) {
                    Ticket::query()->create([
                        'registration_id' => $registration->id,
                        'event_id' => $event->id,
                        'user_id' => $user->id,
                        'status' => 'issued',
                        'price' => $quantity > 0 ? (int) floor($totalAmount / $quantity) : 0,
                        'expires_at' => $event->ends_at,
                    ]);
                }

                $imported++;
            });
        }

        fclose($handle);

        return response()->json([
            'data' => [
                'event_id' => $event->id,
                'imported_count' => $imported,
                'skipped_count' => $skipped,
            ],
        ]);
    }

    private function ownedEvent(Request $request, int $eventId): Event
    {
        return Event::query()
            ->where('id', $eventId)
            ->whereIn('organizer_id', $request->user()->organizers()->pluck('organizers.id'))
            ->firstOrFail();
    }
}

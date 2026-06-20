<?php

namespace App\Services;

use App\Models\Registration;
use League\Csv\Reader;
use League\Csv\Writer;

class AttendeeImportExportService
{
    public static function export(int $eventId): string
    {
        $registrations = Registration::where('event_id', $eventId)
            ->with('user', 'tickets')
            ->get();

        $csv = Writer::createFromString('');
        
        $csv->insertOne([
            'ID', 'Name', 'Email', 'Phone', 'Status', 'Quantity', 
            'Paid Amount', 'Registered At', 'Ticket Numbers'
        ]);

        foreach ($registrations as $reg) {
            $csv->insertOne([
                $reg->id,
                $reg->user->name,
                $reg->user->email,
                $reg->user->phone_e164 ?? '',
                $reg->status,
                $reg->quantity,
                $reg->total_amount,
                $reg->created_at->format('Y-m-d H:i'),
                $reg->tickets->pluck('ticket_number')->implode(','),
            ]);
        }

        return (string)$csv;
    }

    public static function import(int $eventId, string $csvContent): array
    {
        $reader = Reader::createFromString($csvContent);
        $reader->setHeaderOffset(0);

        $imported = 0;
        $errors = [];

        foreach ($reader->getRecords() as $index => $record) {
            try {
                $user = \App\Models\User::firstOrCreate(
                    ['email' => $record['Email']],
                    [
                        'name' => $record['Name'],
                        'phone_e164' => $record['Phone'] ?? null,
                    ]
                );

                Registration::firstOrCreate(
                    ['event_id' => $eventId, 'user_id' => $user->id],
                    [
                        'quantity' => (int)($record['Quantity'] ?? 1),
                        'status' => $record['Status'] ?? 'confirmed',
                        'payment_status' => 'paid',
                        'total_amount' => (int)($record['Paid Amount'] ?? 0),
                    ]
                );

                $imported++;
            } catch (\Exception $e) {
                $errors[$index] = $e->getMessage();
            }
        }

        return ['imported' => $imported, 'errors' => $errors];
    }
}

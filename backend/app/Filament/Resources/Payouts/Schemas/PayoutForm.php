<?php

namespace App\Filament\Resources\Payouts\Schemas;

use Filament\Schemas\Schema;

class PayoutForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('organizer_id')
                    ->label('برگزارکننده')
                    ->relationship('organizer', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                \Filament\Forms\Components\TextInput::make('amount')
                    ->label('مبلغ (ریال)')
                    ->numeric()
                    ->required(),
                \Filament\Forms\Components\Select::make('status')
                    ->label('وضعیت')
                    ->options([
                        'pending' => 'در انتظار',
                        'processing' => 'در حال پردازش',
                        'completed' => 'تکمیل‌شده',
                        'rejected' => 'رد‌شده',
                    ])
                    ->default('pending')
                    ->required(),
                \Filament\Forms\Components\TextInput::make('bank_account')
                    ->label('شماره حساب/شبا'),
                \Filament\Forms\Components\Textarea::make('notes')
                    ->label('یادداشت')
                    ->columnSpanFull(),
                \Filament\Forms\Components\DateTimePicker::make('processed_at')
                    ->label('زمان پردازش'),
            ]);
    }
}

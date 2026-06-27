<?php

namespace App\Filament\Resources\Registrations\Schemas;

use Filament\Schemas\Schema;

class RegistrationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('event_id')
                    ->label('رویداد')
                    ->relationship('event', 'title')
                    ->searchable()
                    ->preload(),
                \Filament\Forms\Components\Select::make('user_id')
                    ->label('کاربر')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                \Filament\Forms\Components\TextInput::make('status')
                    ->label('وضعیت'),
                \Filament\Forms\Components\TextInput::make('payment_status')
                    ->label('وضعیت پرداخت'),
                \Filament\Forms\Components\TextInput::make('quantity')
                    ->label('تعداد')
                    ->numeric(),
                \Filament\Forms\Components\TextInput::make('total_amount')
                    ->label('مبلغ کل (ریال)')
                    ->numeric(),
                \Filament\Forms\Components\TextInput::make('currency')
                    ->label('واحد پول')
                    ->default('IRR'),
                \Filament\Forms\Components\KeyValue::make('form_data')
                    ->label('داده‌های فرم')
                    ->columnSpanFull(),
                \Filament\Forms\Components\DateTimePicker::make('confirmed_at')
                    ->label('زمان تأیید'),
                \Filament\Forms\Components\DateTimePicker::make('cancelled_at')
                    ->label('زمان لغو'),
            ]);
    }
}

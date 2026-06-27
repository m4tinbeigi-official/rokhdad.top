<?php

namespace App\Filament\Resources\Tickets\Schemas;

use Filament\Schemas\Schema;

class TicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('registration_id')
                    ->label('ثبت‌نام')
                    ->relationship('registration', 'id')
                    ->searchable()
                    ->preload(),
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
                \Filament\Forms\Components\TextInput::make('ticket_number')
                    ->label('شماره بلیت'),
                \Filament\Forms\Components\TextInput::make('qr_code_token')
                    ->label('توکن QR'),
                \Filament\Forms\Components\TextInput::make('status')
                    ->label('وضعیت'),
                \Filament\Forms\Components\TextInput::make('price')
                    ->label('قیمت (ریال)')
                    ->numeric(),
                \Filament\Forms\Components\TextInput::make('seat_info')
                    ->label('اطلاعات صندلی'),
                \Filament\Forms\Components\DateTimePicker::make('used_at')
                    ->label('زمان استفاده'),
                \Filament\Forms\Components\DateTimePicker::make('expires_at')
                    ->label('انقضا'),
            ]);
    }
}

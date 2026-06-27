<?php

namespace App\Filament\Resources\Payments\Schemas;

use Filament\Schemas\Schema;

class PaymentForm
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
                \Filament\Forms\Components\Select::make('user_id')
                    ->label('کاربر')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                \Filament\Forms\Components\TextInput::make('gateway')
                    ->label('درگاه'),
                \Filament\Forms\Components\TextInput::make('gateway_authority')
                    ->label('Authority درگاه'),
                \Filament\Forms\Components\TextInput::make('gateway_ref_id')
                    ->label('کد رهگیری درگاه'),
                \Filament\Forms\Components\TextInput::make('status')
                    ->label('وضعیت')
                    ->required(),
                \Filament\Forms\Components\TextInput::make('amount')
                    ->label('مبلغ (ریال)')
                    ->numeric()
                    ->required(),
                \Filament\Forms\Components\TextInput::make('currency')
                    ->label('واحد پول')
                    ->default('IRR'),
                \Filament\Forms\Components\TextInput::make('callback_url')
                    ->label('Callback URL'),
                \Filament\Forms\Components\DateTimePicker::make('paid_at')
                    ->label('زمان پرداخت'),
            ]);
    }
}

<?php

namespace App\Filament\Resources\NotificationLogs\Schemas;

use Filament\Schemas\Schema;

class NotificationLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('user_id')
                    ->label('کاربر')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                \Filament\Forms\Components\TextInput::make('channel')
                    ->label('کانال'),
                \Filament\Forms\Components\TextInput::make('provider')
                    ->label('سرویس‌دهنده'),
                \Filament\Forms\Components\TextInput::make('recipient')
                    ->label('گیرنده'),
                \Filament\Forms\Components\TextInput::make('type')
                    ->label('نوع'),
                \Filament\Forms\Components\Textarea::make('message')
                    ->label('پیام')
                    ->columnSpanFull(),
                \Filament\Forms\Components\TextInput::make('status')
                    ->label('وضعیت'),
                \Filament\Forms\Components\TextInput::make('provider_message_id')
                    ->label('شناسه پیام سرویس‌دهنده'),
                \Filament\Forms\Components\DateTimePicker::make('sent_at')
                    ->label('زمان ارسال'),
            ]);
    }
}

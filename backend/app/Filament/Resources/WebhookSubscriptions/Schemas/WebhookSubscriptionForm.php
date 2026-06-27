<?php

namespace App\Filament\Resources\WebhookSubscriptions\Schemas;

use Filament\Schemas\Schema;

class WebhookSubscriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('organizer_id')
                    ->label('برگزارکننده')
                    ->relationship('organizer', 'name')
                    ->searchable()
                    ->preload(),
                \Filament\Forms\Components\TextInput::make('name')
                    ->label('نام')
                    ->required(),
                \Filament\Forms\Components\TextInput::make('target_url')
                    ->label('آدرس مقصد')
                    ->required()
                    ->url(),
                \Filament\Forms\Components\TextInput::make('secret')
                    ->label('کلید امضا'),
                \Filament\Forms\Components\KeyValue::make('subscribed_events')
                    ->label('رویدادهای مشترک‌شده')
                    ->columnSpanFull(),
                \Filament\Forms\Components\Toggle::make('is_active')
                    ->label('فعال')
                    ->default(true),
                \Filament\Forms\Components\DateTimePicker::make('last_delivered_at')
                    ->label('آخرین تحویل موفق'),
                \Filament\Forms\Components\DateTimePicker::make('last_failed_at')
                    ->label('آخرین خطا'),
            ]);
    }
}

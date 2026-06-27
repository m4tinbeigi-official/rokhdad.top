<?php

namespace App\Filament\Resources\WebhookDeliveries\Schemas;

use Filament\Schemas\Schema;

class WebhookDeliveryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('webhook_subscription_id')
                    ->label('اشتراک')
                    ->relationship('subscription', 'name')
                    ->searchable()
                    ->preload(),
                \Filament\Forms\Components\TextInput::make('event_name')
                    ->label('نام رویداد'),
                \Filament\Forms\Components\TextInput::make('status')
                    ->label('وضعیت'),
                \Filament\Forms\Components\TextInput::make('attempt_count')
                    ->label('تعداد تلاش')
                    ->numeric(),
                \Filament\Forms\Components\TextInput::make('response_status')
                    ->label('کد پاسخ')
                    ->numeric(),
                \Filament\Forms\Components\DateTimePicker::make('delivered_at')
                    ->label('زمان تحویل'),
                \Filament\Forms\Components\DateTimePicker::make('failed_at')
                    ->label('زمان خطا'),
            ]);
    }
}

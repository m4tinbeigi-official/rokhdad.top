<?php

namespace App\Filament\Resources\EventPromoCodes\Schemas;

use Filament\Schemas\Schema;

class EventPromoCodeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('event_id')
                    ->label('رویداد')
                    ->relationship('event', 'title')
                    ->searchable()
                    ->preload()
                    ->required(),
                \Filament\Forms\Components\TextInput::make('code')
                    ->label('کد')
                    ->required(),
                \Filament\Forms\Components\TextInput::make('discount_type')
                    ->label('نوع تخفیف'),
                \Filament\Forms\Components\TextInput::make('discount_value')
                    ->label('مقدار تخفیف')
                    ->numeric(),
                \Filament\Forms\Components\TextInput::make('max_uses')
                    ->label('حداکثر استفاده')
                    ->numeric(),
                \Filament\Forms\Components\TextInput::make('used_count')
                    ->label('استفاده‌شده')
                    ->numeric(),
                \Filament\Forms\Components\TextInput::make('min_quantity')
                    ->label('حداقل تعداد')
                    ->numeric(),
                \Filament\Forms\Components\TextInput::make('max_quantity')
                    ->label('حداکثر تعداد')
                    ->numeric(),
                \Filament\Forms\Components\Toggle::make('is_active')
                    ->label('فعال')
                    ->default(true),
                \Filament\Forms\Components\DateTimePicker::make('starts_at')
                    ->label('شروع'),
                \Filament\Forms\Components\DateTimePicker::make('ends_at')
                    ->label('پایان'),
            ]);
    }
}

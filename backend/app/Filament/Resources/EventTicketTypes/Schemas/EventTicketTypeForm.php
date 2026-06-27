<?php

namespace App\Filament\Resources\EventTicketTypes\Schemas;

use Filament\Schemas\Schema;

class EventTicketTypeForm
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
                \Filament\Forms\Components\TextInput::make('name')
                    ->label('نام')
                    ->required(),
                \Filament\Forms\Components\Textarea::make('description')
                    ->label('توضیحات')
                    ->columnSpanFull(),
                \Filament\Forms\Components\TextInput::make('price')
                    ->label('قیمت (ریال)')
                    ->numeric()
                    ->required(),
                \Filament\Forms\Components\TextInput::make('currency')
                    ->label('واحد پول')
                    ->default('IRR'),
                \Filament\Forms\Components\TextInput::make('capacity')
                    ->label('ظرفیت')
                    ->numeric(),
                \Filament\Forms\Components\TextInput::make('sold_count')
                    ->label('فروخته‌شده')
                    ->numeric(),
                \Filament\Forms\Components\TextInput::make('max_per_user')
                    ->label('حداکثر هر کاربر')
                    ->numeric(),
                \Filament\Forms\Components\TextInput::make('sort_order')
                    ->label('ترتیب')
                    ->numeric(),
                \Filament\Forms\Components\Toggle::make('is_active')
                    ->label('فعال')
                    ->default(true),
                \Filament\Forms\Components\DateTimePicker::make('sale_starts_at')
                    ->label('شروع فروش'),
                \Filament\Forms\Components\DateTimePicker::make('sale_ends_at')
                    ->label('پایان فروش'),
            ]);
    }
}

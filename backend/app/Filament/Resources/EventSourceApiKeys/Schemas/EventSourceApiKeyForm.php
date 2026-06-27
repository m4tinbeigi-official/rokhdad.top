<?php

namespace App\Filament\Resources\EventSourceApiKeys\Schemas;

use Filament\Schemas\Schema;

class EventSourceApiKeyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('event_source_id')
                    ->label('منبع رویداد')
                    ->relationship('source', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                \Filament\Forms\Components\TextInput::make('name')
                    ->label('نام')
                    ->required(),
                \Filament\Forms\Components\TextInput::make('status')
                    ->label('وضعیت')
                    ->default('active'),
                \Filament\Forms\Components\KeyValue::make('metadata')
                    ->label('متادیتا')
                    ->columnSpanFull(),
                \Filament\Forms\Components\DateTimePicker::make('active_from')
                    ->label('فعال از'),
                \Filament\Forms\Components\DateTimePicker::make('expires_at')
                    ->label('انقضا'),
                \Filament\Forms\Components\DateTimePicker::make('last_used_at')
                    ->label('آخرین استفاده'),
                \Filament\Forms\Components\DateTimePicker::make('rotated_at')
                    ->label('آخرین چرخش'),
            ]);
    }
}

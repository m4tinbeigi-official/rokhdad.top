<?php

namespace App\Filament\Resources\EventFieldOverrides\Schemas;

use Filament\Schemas\Schema;

class EventFieldOverrideForm
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
                \Filament\Forms\Components\TextInput::make('field_path')
                    ->label('مسیر فیلد')
                    ->required(),
                \Filament\Forms\Components\TextInput::make('source_key')
                    ->label('منبع'),
                \Filament\Forms\Components\Select::make('applied_by_user_id')
                    ->label('اعمال‌شده توسط')
                    ->relationship('appliedBy', 'name')
                    ->searchable()
                    ->preload(),
                \Filament\Forms\Components\DateTimePicker::make('applied_at')
                    ->label('زمان اعمال'),
            ]);
    }
}

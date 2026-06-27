<?php

namespace App\Filament\Resources\EventFieldLocks\Schemas;

use Filament\Schemas\Schema;

class EventFieldLockForm
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
                \Filament\Forms\Components\Select::make('locked_by_user_id')
                    ->label('قفل‌شده توسط')
                    ->relationship('lockedBy', 'name')
                    ->searchable()
                    ->preload(),
                \Filament\Forms\Components\Textarea::make('reason')
                    ->label('دلیل')
                    ->columnSpanFull(),
                \Filament\Forms\Components\DateTimePicker::make('locked_at')
                    ->label('زمان قفل'),
            ]);
    }
}

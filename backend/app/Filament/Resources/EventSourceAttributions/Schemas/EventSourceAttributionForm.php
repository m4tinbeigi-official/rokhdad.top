<?php

namespace App\Filament\Resources\EventSourceAttributions\Schemas;

use Filament\Schemas\Schema;

class EventSourceAttributionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('event_id')
                    ->label('رویداد')
                    ->relationship('event', 'title')
                    ->searchable()
                    ->preload(),
                \Filament\Forms\Components\TextInput::make('source_key')
                    ->label('کلید منبع'),
                \Filament\Forms\Components\TextInput::make('external_id')
                    ->label('شناسه خارجی'),
                \Filament\Forms\Components\TextInput::make('external_url')
                    ->label('لینک خارجی'),
                \Filament\Forms\Components\TextInput::make('sync_status')
                    ->label('وضعیت همگام‌سازی'),
                \Filament\Forms\Components\TextInput::make('confidence_score')
                    ->label('امتیاز اطمینان'),
                \Filament\Forms\Components\KeyValue::make('metadata')
                    ->label('متادیتا')
                    ->columnSpanFull(),
                \Filament\Forms\Components\DateTimePicker::make('first_seen_at')
                    ->label('اولین مشاهده'),
                \Filament\Forms\Components\DateTimePicker::make('last_seen_at')
                    ->label('آخرین مشاهده'),
                \Filament\Forms\Components\DateTimePicker::make('last_synced_at')
                    ->label('آخرین همگام‌سازی'),
            ]);
    }
}

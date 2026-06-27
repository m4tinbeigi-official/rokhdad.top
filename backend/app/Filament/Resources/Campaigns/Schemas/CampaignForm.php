<?php

namespace App\Filament\Resources\Campaigns\Schemas;

use Filament\Schemas\Schema;

class CampaignForm
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
                \Filament\Forms\Components\Textarea::make('description')
                    ->label('توضیحات')
                    ->columnSpanFull(),
                \Filament\Forms\Components\TextInput::make('channel')
                    ->label('کانال'),
                \Filament\Forms\Components\TextInput::make('status')
                    ->label('وضعیت'),
                \Filament\Forms\Components\Textarea::make('template')
                    ->label('قالب')
                    ->columnSpanFull(),
                \Filament\Forms\Components\KeyValue::make('target_audience')
                    ->label('مخاطب هدف')
                    ->columnSpanFull(),
                \Filament\Forms\Components\KeyValue::make('settings')
                    ->label('تنظیمات')
                    ->columnSpanFull(),
                \Filament\Forms\Components\DateTimePicker::make('starts_at')
                    ->label('شروع'),
                \Filament\Forms\Components\DateTimePicker::make('ends_at')
                    ->label('پایان'),
            ]);
    }
}

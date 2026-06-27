<?php

namespace App\Filament\Resources\UserPreferences\Schemas;

use Filament\Schemas\Schema;

class UserPreferenceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('user_id')
                    ->label('کاربر')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                \Filament\Forms\Components\TextInput::make('preferred_event_type')
                    ->label('نوع رویداد ترجیحی'),
                \Filament\Forms\Components\TextInput::make('notification_channel')
                    ->label('کانال اطلاع‌رسانی'),
                \Filament\Forms\Components\KeyValue::make('favorite_category_ids')
                    ->label('دسته‌های دلخواه')
                    ->columnSpanFull(),
                \Filament\Forms\Components\KeyValue::make('favorite_city_ids')
                    ->label('شهرهای دلخواه')
                    ->columnSpanFull(),
                \Filament\Forms\Components\Toggle::make('notify_new_events')
                    ->label('اطلاع رویداد جدید'),
                \Filament\Forms\Components\Toggle::make('notify_reminders')
                    ->label('یادآوری‌ها'),
            ]);
    }
}

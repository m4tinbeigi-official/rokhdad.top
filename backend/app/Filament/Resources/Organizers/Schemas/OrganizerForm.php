<?php

namespace App\Filament\Resources\Organizers\Schemas;

use Filament\Schemas\Schema;

class OrganizerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\TextInput::make('name')
                    ->label('نام برگزارکننده')
                    ->required()
                    ->maxLength(255),
                \Filament\Forms\Components\TextInput::make('slug')
                    ->label('نامک')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                \Filament\Forms\Components\TextInput::make('source_key')
                    ->label('منبع (Source)')
                    ->maxLength(255),
                \Filament\Forms\Components\TextInput::make('external_id')
                    ->label('شناسه خارجی (External ID)')
                    ->maxLength(255),
                \Filament\Forms\Components\Select::make('city_id')
                    ->label('شهر')
                    ->relationship('city', 'name')
                    ->searchable()
                    ->preload(),
                \Filament\Forms\Components\TextInput::make('website_url')
                    ->label('وب‌سایت')
                    ->url()
                    ->maxLength(255),
                \Filament\Forms\Components\TextInput::make('email')
                    ->label('ایمیل')
                    ->email()
                    ->maxLength(255),
                \Filament\Forms\Components\TextInput::make('phone_e164')
                    ->label('شماره تماس')
                    ->maxLength(20),
                \Filament\Forms\Components\Textarea::make('description')
                    ->label('توضیحات')
                    ->columnSpanFull(),
                \Filament\Forms\Components\Toggle::make('is_active')
                    ->label('فعال')
                    ->default(true),
            ]);
    }
}

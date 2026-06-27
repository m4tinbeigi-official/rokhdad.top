<?php

namespace App\Filament\Resources\People\Schemas;

use Filament\Schemas\Schema;

class PersonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\TextInput::make('full_name')
                    ->label('نام کامل')
                    ->required()
                    ->maxLength(255),
                \Filament\Forms\Components\TextInput::make('slug')
                    ->label('نامک')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                \Filament\Forms\Components\TextInput::make('title')
                    ->label('عنوان')
                    ->maxLength(255),
                \Filament\Forms\Components\TextInput::make('email')
                    ->label('ایمیل')
                    ->email()
                    ->maxLength(255),
                \Filament\Forms\Components\TextInput::make('phone_e164')
                    ->label('شماره تماس')
                    ->maxLength(20),
                \Filament\Forms\Components\TextInput::make('website_url')
                    ->label('وب‌سایت')
                    ->url()
                    ->maxLength(255),
                \Filament\Forms\Components\Textarea::make('bio')
                    ->label('درباره')
                    ->columnSpanFull(),
                \Filament\Forms\Components\Toggle::make('is_active')
                    ->label('فعال')
                    ->default(true),
            ]);
    }
}

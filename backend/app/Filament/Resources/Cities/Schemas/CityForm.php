<?php

namespace App\Filament\Resources\Cities\Schemas;

use Filament\Schemas\Schema;

class CityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\TextInput::make('name')
                    ->label('نام')
                    ->required(),
                \Filament\Forms\Components\TextInput::make('slug')
                    ->label('نامک')
                    ->required()
                    ->unique(ignoreRecord: true),
                \Filament\Forms\Components\TextInput::make('province')
                    ->label('استان'),
                \Filament\Forms\Components\TextInput::make('country_code')
                    ->label('کد کشور')
                    ->default('IR'),
                \Filament\Forms\Components\TextInput::make('latitude')
                    ->label('عرض جغرافیایی'),
                \Filament\Forms\Components\TextInput::make('longitude')
                    ->label('طول جغرافیایی'),
                \Filament\Forms\Components\TextInput::make('sort_order')
                    ->label('ترتیب')
                    ->numeric(),
                \Filament\Forms\Components\Toggle::make('is_active')
                    ->label('فعال')
                    ->default(true),
            ]);
    }
}

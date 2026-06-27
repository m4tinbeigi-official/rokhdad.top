<?php

namespace App\Filament\Resources\AiServices\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;

class AiServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('name')
                ->label('نام سرویس')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('base_url')
                ->label('آدرس پایه')
                ->required()
                ->url()
                ->maxLength(255),

            Forms\Components\TextInput::make('api_key')
                ->label('کلید API')
                ->required()
                ->password()
                ->revealable()
                ->maxLength(65535),

            Forms\Components\TextInput::make('model_name')
                ->label('نام مدل (اختیاری)')
                ->maxLength(255),

            Forms\Components\Toggle::make('is_active')
                ->label('فعال')
                ->default(false),
        ]);
    }
}

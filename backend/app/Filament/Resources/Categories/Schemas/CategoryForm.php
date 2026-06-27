<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('parent_id')
                    ->label('دسته والد')
                    ->relationship('parent', 'name')
                    ->searchable()
                    ->preload(),
                \Filament\Forms\Components\TextInput::make('name')
                    ->label('نام')
                    ->required(),
                \Filament\Forms\Components\TextInput::make('slug')
                    ->label('نامک')
                    ->required()
                    ->unique(ignoreRecord: true),
                \Filament\Forms\Components\Textarea::make('description')
                    ->label('توضیحات')
                    ->columnSpanFull(),
                \Filament\Forms\Components\TextInput::make('sort_order')
                    ->label('ترتیب')
                    ->numeric(),
                \Filament\Forms\Components\Toggle::make('is_active')
                    ->label('فعال')
                    ->default(true),
            ]);
    }
}

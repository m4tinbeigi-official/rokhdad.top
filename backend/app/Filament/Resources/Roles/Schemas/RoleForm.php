<?php

namespace App\Filament\Resources\Roles\Schemas;

use Filament\Schemas\Schema;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\TextInput::make('name')
                    ->label('نام سیستمی')
                    ->required()
                    ->unique(ignoreRecord: true),
                \Filament\Forms\Components\TextInput::make('label')
                    ->label('عنوان نمایشی'),
                \Filament\Forms\Components\Select::make('permissions')
                    ->label('دسترسی‌ها')
                    ->relationship('permissions', 'label')
                    ->multiple()
                    ->searchable()
                    ->preload(),
            ]);
    }
}

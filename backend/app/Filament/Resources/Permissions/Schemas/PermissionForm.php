<?php

namespace App\Filament\Resources\Permissions\Schemas;

use Filament\Schemas\Schema;

class PermissionForm
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
            ]);
    }
}

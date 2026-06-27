<?php

namespace App\Filament\Resources\HermesErrors\Schemas;

use Filament\Schemas\Schema;

class HermesErrorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\TextInput::make('type')
                    ->label('نوع')
                    ->disabled(),
                \Filament\Forms\Components\Textarea::make('message')
                    ->label('پیام')
                    ->disabled()
                    ->columnSpanFull(),
                \Filament\Forms\Components\Textarea::make('trace')
                    ->label('Trace')
                    ->disabled()
                    ->rows(10)
                    ->columnSpanFull(),
                \Filament\Forms\Components\KeyValue::make('payload')
                    ->label('داده‌ها')
                    ->disabled()
                    ->columnSpanFull(),
            ]);
    }
}

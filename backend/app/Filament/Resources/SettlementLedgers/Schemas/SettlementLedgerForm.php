<?php

namespace App\Filament\Resources\SettlementLedgers\Schemas;

use Filament\Schemas\Schema;

class SettlementLedgerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('organizer_id')
                    ->label('برگزارکننده')
                    ->relationship('organizer', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                \Filament\Forms\Components\TextInput::make('reference_type')
                    ->label('نوع مرجع'),
                \Filament\Forms\Components\TextInput::make('reference_id')
                    ->label('شناسه مرجع')
                    ->numeric(),
                \Filament\Forms\Components\TextInput::make('type')
                    ->label('نوع تراکنش'),
                \Filament\Forms\Components\TextInput::make('amount')
                    ->label('مبلغ (ریال)')
                    ->numeric(),
                \Filament\Forms\Components\TextInput::make('balance_before')
                    ->label('مانده قبل')
                    ->numeric(),
                \Filament\Forms\Components\TextInput::make('balance_after')
                    ->label('مانده بعد')
                    ->numeric(),
                \Filament\Forms\Components\Textarea::make('description')
                    ->label('توضیحات')
                    ->columnSpanFull(),
            ]);
    }
}

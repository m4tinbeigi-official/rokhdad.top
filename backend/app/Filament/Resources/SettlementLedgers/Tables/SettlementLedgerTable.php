<?php

namespace App\Filament\Resources\SettlementLedgers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class SettlementLedgerTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('organizer.name')
                    ->label('برگزارکننده')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('type')
                    ->label('نوع'),
                \Filament\Tables\Columns\TextColumn::make('amount')
                    ->label('مبلغ')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('balance_after')
                    ->label('مانده')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('reference_type')
                    ->label('مرجع'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

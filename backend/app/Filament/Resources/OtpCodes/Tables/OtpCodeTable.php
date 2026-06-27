<?php

namespace App\Filament\Resources\OtpCodes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class OtpCodeTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('phone')
                    ->label('تلفن')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('purpose')
                    ->label('هدف'),
                \Filament\Tables\Columns\TextColumn::make('attempts')
                    ->label('تلاش'),
                \Filament\Tables\Columns\IconColumn::make('used')
                    ->label('استفاده‌شده')
                    ->boolean(),
                \Filament\Tables\Columns\TextColumn::make('expires_at')
                    ->label('انقضا')
                    ->dateTime()
                    ->sortable(),
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

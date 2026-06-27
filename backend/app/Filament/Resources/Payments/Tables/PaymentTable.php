<?php

namespace App\Filament\Resources\Payments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class PaymentTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('user.name')
                    ->label('کاربر')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('gateway')
                    ->label('درگاه')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('status')
                    ->label('وضعیت')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('amount')
                    ->label('مبلغ')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('paid_at')
                    ->label('زمان پرداخت')
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

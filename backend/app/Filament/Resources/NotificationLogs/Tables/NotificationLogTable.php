<?php

namespace App\Filament\Resources\NotificationLogs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class NotificationLogTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('channel')
                    ->label('کانال')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('recipient')
                    ->label('گیرنده')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('type')
                    ->label('نوع'),
                \Filament\Tables\Columns\TextColumn::make('status')
                    ->label('وضعیت')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('sent_at')
                    ->label('زمان ارسال')
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

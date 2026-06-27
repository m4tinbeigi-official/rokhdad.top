<?php

namespace App\Filament\Resources\Tickets\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class TicketTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('ticket_number')
                    ->label('شماره بلیت')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('event.title')
                    ->label('رویداد')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('user.name')
                    ->label('کاربر')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('status')
                    ->label('وضعیت')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('price')
                    ->label('قیمت')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('used_at')
                    ->label('زمان استفاده')
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

<?php

namespace App\Filament\Resources\EventSourceAttributions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class EventSourceAttributionTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('event.title')
                    ->label('رویداد')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('source_key')
                    ->label('منبع')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('external_id')
                    ->label('شناسه خارجی')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('sync_status')
                    ->label('وضعیت'),
                \Filament\Tables\Columns\TextColumn::make('last_synced_at')
                    ->label('آخرین همگام‌سازی')
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

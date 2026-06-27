<?php

namespace App\Filament\Resources\EventFieldOverrides\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class EventFieldOverrideTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('event.title')
                    ->label('رویداد')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('field_path')
                    ->label('فیلد')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('source_key')
                    ->label('منبع'),
                \Filament\Tables\Columns\TextColumn::make('applied_at')
                    ->label('زمان اعمال')
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

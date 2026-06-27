<?php

namespace App\Filament\Resources\Campaigns\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class CampaignTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('name')
                    ->label('نام')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('organizer.name')
                    ->label('برگزارکننده')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('channel')
                    ->label('کانال'),
                \Filament\Tables\Columns\TextColumn::make('status')
                    ->label('وضعیت')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('starts_at')
                    ->label('شروع')
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

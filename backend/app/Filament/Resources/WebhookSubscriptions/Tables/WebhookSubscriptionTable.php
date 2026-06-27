<?php

namespace App\Filament\Resources\WebhookSubscriptions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class WebhookSubscriptionTable
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
                \Filament\Tables\Columns\TextColumn::make('target_url')
                    ->label('مقصد')
                    ->searchable(),
                \Filament\Tables\Columns\IconColumn::make('is_active')
                    ->label('فعال')
                    ->boolean(),
                \Filament\Tables\Columns\TextColumn::make('last_delivered_at')
                    ->label('آخرین تحویل')
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

<?php

namespace App\Filament\Resources\WebhookDeliveries\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class WebhookDeliveryTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('subscription.name')
                    ->label('اشتراک')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('event_name')
                    ->label('رویداد')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('status')
                    ->label('وضعیت')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('attempt_count')
                    ->label('تلاش'),
                \Filament\Tables\Columns\TextColumn::make('delivered_at')
                    ->label('زمان تحویل')
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

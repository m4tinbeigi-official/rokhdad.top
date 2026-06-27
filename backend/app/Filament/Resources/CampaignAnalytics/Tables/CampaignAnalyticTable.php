<?php

namespace App\Filament\Resources\CampaignAnalytics\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class CampaignAnalyticTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('campaign.name')
                    ->label('کمپین')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('metric_type')
                    ->label('متریک')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('value')
                    ->label('مقدار')
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

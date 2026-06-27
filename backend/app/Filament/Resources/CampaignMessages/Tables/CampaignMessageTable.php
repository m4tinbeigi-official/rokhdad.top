<?php

namespace App\Filament\Resources\CampaignMessages\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class CampaignMessageTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('campaign.name')
                    ->label('کمپین')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('type')
                    ->label('نوع'),
                \Filament\Tables\Columns\TextColumn::make('subject')
                    ->label('موضوع')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('status')
                    ->label('وضعیت'),
                \Filament\Tables\Columns\TextColumn::make('send_at')
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

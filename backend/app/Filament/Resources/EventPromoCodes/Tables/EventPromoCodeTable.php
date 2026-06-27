<?php

namespace App\Filament\Resources\EventPromoCodes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class EventPromoCodeTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('code')
                    ->label('کد')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('event.title')
                    ->label('رویداد')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('discount_type')
                    ->label('نوع'),
                \Filament\Tables\Columns\TextColumn::make('discount_value')
                    ->label('مقدار'),
                \Filament\Tables\Columns\TextColumn::make('used_count')
                    ->label('استفاده‌شده'),
                \Filament\Tables\Columns\IconColumn::make('is_active')
                    ->label('فعال')
                    ->boolean(),
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

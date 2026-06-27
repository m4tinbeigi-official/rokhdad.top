<?php

namespace App\Filament\Resources\EventTicketTypes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class EventTicketTypeTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('event.title')
                    ->label('رویداد')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('name')
                    ->label('نام')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('price')
                    ->label('قیمت')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('capacity')
                    ->label('ظرفیت'),
                \Filament\Tables\Columns\TextColumn::make('sold_count')
                    ->label('فروخته‌شده'),
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

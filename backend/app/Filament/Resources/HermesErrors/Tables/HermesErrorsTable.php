<?php

namespace App\Filament\Resources\HermesErrors\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;

class HermesErrorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('type')
                    ->label('نوع')
                    ->badge()
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('message')
                    ->label('پیام')
                    ->limit(80)
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('created_at')
                    ->label('زمان')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('type')
                    ->label('نوع')
                    ->options([
                        'hermes' => 'hermes',
                        'exception' => 'exception',
                        'validation' => 'validation',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

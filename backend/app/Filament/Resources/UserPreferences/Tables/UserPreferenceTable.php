<?php

namespace App\Filament\Resources\UserPreferences\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class UserPreferenceTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('user.name')
                    ->label('کاربر')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('preferred_event_type')
                    ->label('نوع ترجیحی'),
                \Filament\Tables\Columns\TextColumn::make('notification_channel')
                    ->label('کانال'),
                \Filament\Tables\Columns\IconColumn::make('notify_new_events')
                    ->label('رویداد جدید')
                    ->boolean(),
                \Filament\Tables\Columns\IconColumn::make('notify_reminders')
                    ->label('یادآوری')
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

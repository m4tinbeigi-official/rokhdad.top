<?php

namespace App\Filament\Resources\AuditLogs\Schemas;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AuditLogTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('زمان')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),

                TextColumn::make('action')
                    ->label('عملیات')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        str_starts_with((string) $state, 'payout') => 'warning',
                        str_starts_with((string) $state, 'auth') => 'info',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),

                TextColumn::make('actor_label')
                    ->label('کاربر')
                    ->placeholder('سیستم')
                    ->searchable(),

                TextColumn::make('description')
                    ->label('توضیح')
                    ->limit(60)
                    ->placeholder('—'),

                TextColumn::make('subject_type')
                    ->label('موضوع')
                    ->formatStateUsing(fn ($state, $record) => $state ? class_basename($state).' #'.$record->subject_id : '—')
                    ->toggleable(),

                TextColumn::make('ip_address')
                    ->label('IP')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label('عملیات')
                    ->options([
                        'auth.login' => 'ورود',
                        'auth.logout' => 'خروج',
                        'payout.completed' => 'تسویه تکمیل‌شده',
                        'payout.rejected' => 'تسویه ردشده',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }
}

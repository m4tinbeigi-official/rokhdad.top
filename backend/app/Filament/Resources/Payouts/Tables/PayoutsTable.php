<?php

namespace App\Filament\Resources\Payouts\Tables;

use App\Models\Payout;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class PayoutsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('organizer.name')
                    ->label('برگزارکننده')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('amount')
                    ->label('مبلغ')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('bank_account')
                    ->label('حساب'),
                \Filament\Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ درخواست')
                    ->dateTime()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('processed_at')
                    ->label('زمان پردازش')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options([
                        'pending' => 'در انتظار',
                        'processing' => 'در حال پردازش',
                        'completed' => 'تکمیل‌شده',
                        'rejected' => 'رد‌شده',
                    ]),
            ])
            ->recordActions([
                Action::make('complete')
                    ->label('تکمیل و ثبت در دفتر')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Payout $record): bool => $record->status !== 'completed')
                    ->action(fn (Payout $record) => $record->markCompleted()),
                Action::make('reject')
                    ->label('رد درخواست')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Payout $record): bool => ! in_array($record->status, ['completed', 'rejected'], true))
                    ->action(fn (Payout $record) => $record->reject()),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

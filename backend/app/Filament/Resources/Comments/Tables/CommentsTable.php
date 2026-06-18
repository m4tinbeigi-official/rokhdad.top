<?php

namespace App\Filament\Resources\Comments\Tables;

use App\Models\Comment;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CommentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('event.title')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('user.name')
                    ->searchable(),
                TextColumn::make('body')
                    ->searchable()
                    ->limit(80),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                IconColumn::make('is_pinned')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('approve')
                    ->visible(fn (Comment $record): bool => $record->status !== 'approved')
                    ->action(fn (Comment $record) => $record->approve()),
                Action::make('reject')
                    ->visible(fn (Comment $record): bool => $record->status !== 'rejected')
                    ->action(fn (Comment $record) => $record->reject()),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

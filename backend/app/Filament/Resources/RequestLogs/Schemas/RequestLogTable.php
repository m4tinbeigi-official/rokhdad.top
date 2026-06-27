<?php

namespace App\Filament\Resources\RequestLogs\Schemas;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RequestLogTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('eventSource.source_key')
                    ->label('منبع')
                    ->badge()
                    ->sortable()
                    ->searchable(),

                TextColumn::make('method')
                    ->label('متد')
                    ->badge(),

                TextColumn::make('url')
                    ->label('آدرس')
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->url)
                    ->searchable(),

                TextColumn::make('status_code')
                    ->label('کد وضعیت')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 200 && $state < 300 => 'success',
                        $state === 403 || $state === 429 || $state === 503 => 'danger',
                        $state >= 400 => 'warning',
                        default       => 'gray',
                    })
                    ->sortable(),

                IconColumn::make('used_proxy')
                    ->label('از پروکسی؟')
                    ->boolean(),

                TextColumn::make('proxy_url')
                    ->label('پروکسی')
                    ->limit(30)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('duration_ms')
                    ->label('زمان (ms)')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? "{$state} ms" : '—'),

                TextColumn::make('error_message')
                    ->label('خطا')
                    ->limit(40)
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->label('زمان ارسال')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('event_source_id')
                    ->relationship('eventSource', 'source_key')
                    ->label('منبع'),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('10s');
    }
}

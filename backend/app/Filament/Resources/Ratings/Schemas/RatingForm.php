<?php

namespace App\Filament\Resources\Ratings\Schemas;

use Filament\Schemas\Schema;

class RatingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('event_id')
                    ->label('رویداد')
                    ->relationship('event', 'title')
                    ->searchable()
                    ->preload()
                    ->required(),
                \Filament\Forms\Components\Select::make('user_id')
                    ->label('کاربر')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                \Filament\Forms\Components\TextInput::make('score')
                    ->label('امتیاز')
                    ->numeric()
                    ->required(),
                \Filament\Forms\Components\Textarea::make('review')
                    ->label('نظر')
                    ->columnSpanFull(),
            ]);
    }
}

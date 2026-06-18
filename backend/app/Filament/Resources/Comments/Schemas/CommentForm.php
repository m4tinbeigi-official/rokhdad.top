<?php

namespace App\Filament\Resources\Comments\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CommentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('event.title')
                    ->label('Event')
                    ->disabled(),
                TextInput::make('user.name')
                    ->label('User')
                    ->disabled(),
                Textarea::make('body')
                    ->required()
                    ->maxLength(2000)
                    ->columnSpanFull(),
                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'spam' => 'Spam',
                    ])
                    ->required(),
                Toggle::make('is_pinned'),
                DateTimePicker::make('approved_at'),
            ]);
    }
}

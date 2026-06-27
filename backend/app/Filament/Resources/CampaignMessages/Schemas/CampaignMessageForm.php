<?php

namespace App\Filament\Resources\CampaignMessages\Schemas;

use Filament\Schemas\Schema;

class CampaignMessageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('campaign_id')
                    ->label('کمپین')
                    ->relationship('campaign', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                \Filament\Forms\Components\TextInput::make('type')
                    ->label('نوع'),
                \Filament\Forms\Components\TextInput::make('subject')
                    ->label('موضوع'),
                \Filament\Forms\Components\Textarea::make('body')
                    ->label('متن')
                    ->columnSpanFull(),
                \Filament\Forms\Components\TextInput::make('status')
                    ->label('وضعیت'),
                \Filament\Forms\Components\TextInput::make('sent_count')
                    ->label('تعداد ارسال')
                    ->numeric(),
                \Filament\Forms\Components\DateTimePicker::make('send_at')
                    ->label('زمان ارسال'),
            ]);
    }
}

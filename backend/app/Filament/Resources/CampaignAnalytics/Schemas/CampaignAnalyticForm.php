<?php

namespace App\Filament\Resources\CampaignAnalytics\Schemas;

use Filament\Schemas\Schema;

class CampaignAnalyticForm
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
                \Filament\Forms\Components\TextInput::make('metric_type')
                    ->label('نوع متریک'),
                \Filament\Forms\Components\TextInput::make('value')
                    ->label('مقدار')
                    ->numeric(),
                \Filament\Forms\Components\KeyValue::make('details')
                    ->label('جزئیات')
                    ->columnSpanFull(),
            ]);
    }
}

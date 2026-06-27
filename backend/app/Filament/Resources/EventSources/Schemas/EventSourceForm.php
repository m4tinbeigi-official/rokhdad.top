<?php

namespace App\Filament\Resources\EventSources\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class EventSourceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('source_key')
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('name')
                    ->required(),
                TextInput::make('base_url')
                    ->url(),
                TextInput::make('api_base_url')
                    ->url()
                    ->nullable(),
                TextInput::make('proxy_url')
                    ->url()
                    ->nullable(),
                Select::make('auth_type')
                    ->options([
                        'none' => 'None',
                        'api_key' => 'API key',
                        'oauth' => 'OAuth',
                        'session' => 'Session',
                    ])
                    ->required()
                    ->default('none'),
                Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'paused' => 'Paused',
                        'disabled' => 'Disabled',
                        'failing' => 'Failing',
                    ])
                    ->required()
                    ->default('active'),
                Select::make('health_status')
                    ->options([
                        'unknown' => 'Unknown',
                        'healthy' => 'Healthy',
                        'degraded' => 'Degraded',
                        'failing' => 'Failing',
                    ])
                    ->required()
                    ->default('unknown'),
                TextInput::make('consecutive_failures')
                    ->numeric()
                    ->minValue(0)
                    ->default(0),
                Toggle::make('is_enabled')
                    ->default(true),
                TextInput::make('rate_limit_per_minute')
                    ->numeric()
                    ->minValue(1),
                KeyValue::make('config'),
                DateTimePicker::make('last_checked_at'),
                DateTimePicker::make('last_success_at'),
                DateTimePicker::make('last_failure_at'),
                TextInput::make('last_error_message'),
            ]);
    }
}

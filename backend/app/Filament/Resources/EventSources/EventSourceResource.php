<?php

namespace App\Filament\Resources\EventSources;

use App\Filament\Resources\EventSources\Pages\CreateEventSource;
use App\Filament\Resources\EventSources\Pages\EditEventSource;
use App\Filament\Resources\EventSources\Pages\ListEventSources;
use App\Filament\Resources\EventSources\Schemas\EventSourceForm;
use App\Filament\Resources\EventSources\Tables\EventSourcesTable;
use App\Models\EventSource;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EventSourceResource extends Resource
{
    protected static ?string $model = EventSource::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServerStack;

    protected static ?string $modelLabel = 'منبع رویداد';
    protected static ?string $pluralModelLabel = 'منابع رویداد';
    protected static ?string $navigationLabel = 'منابع رویداد';
    protected static \UnitEnum|string|null $navigationGroup = 'تنظیمات سیستم';

    public static function form(Schema $schema): Schema
    {
        return EventSourceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EventSourcesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEventSources::route('/'),
            'create' => CreateEventSource::route('/create'),
            'edit' => EditEventSource::route('/{record}/edit'),
        ];
    }
}

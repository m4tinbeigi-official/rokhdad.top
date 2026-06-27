<?php

namespace App\Filament\Resources\EventSourceAttributions;

use App\Filament\Resources\EventSourceAttributions\Pages\CreateEventSourceAttribution;
use App\Filament\Resources\EventSourceAttributions\Pages\EditEventSourceAttribution;
use App\Filament\Resources\EventSourceAttributions\Pages\ListEventSourceAttributions;
use App\Filament\Resources\EventSourceAttributions\Schemas\EventSourceAttributionForm;
use App\Filament\Resources\EventSourceAttributions\Tables\EventSourceAttributionTable;
use App\Models\EventSourceAttribution;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EventSourceAttributionResource extends Resource
{
    protected static ?string $model = EventSourceAttribution::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'انتساب منبع';
    protected static ?string $pluralModelLabel = 'انتساب‌های منبع';
    protected static ?string $navigationLabel = 'انتساب منبع';
    protected static \UnitEnum|string|null $navigationGroup = 'کیفیت داده و منابع';

    public static function form(Schema $schema): Schema
    {
        return EventSourceAttributionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EventSourceAttributionTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEventSourceAttributions::route('/'),
            'create' => CreateEventSourceAttribution::route('/create'),
            'edit' => EditEventSourceAttribution::route('/{record}/edit'),
        ];
    }
}

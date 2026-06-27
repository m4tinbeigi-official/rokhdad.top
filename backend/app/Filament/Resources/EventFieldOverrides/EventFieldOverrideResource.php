<?php

namespace App\Filament\Resources\EventFieldOverrides;

use App\Filament\Resources\EventFieldOverrides\Pages\CreateEventFieldOverride;
use App\Filament\Resources\EventFieldOverrides\Pages\EditEventFieldOverride;
use App\Filament\Resources\EventFieldOverrides\Pages\ListEventFieldOverrides;
use App\Filament\Resources\EventFieldOverrides\Schemas\EventFieldOverrideForm;
use App\Filament\Resources\EventFieldOverrides\Tables\EventFieldOverrideTable;
use App\Models\EventFieldOverride;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EventFieldOverrideResource extends Resource
{
    protected static ?string $model = EventFieldOverride::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'بازنویسی فیلد';
    protected static ?string $pluralModelLabel = 'بازنویسی فیلدها';
    protected static ?string $navigationLabel = 'بازنویسی فیلد رویداد';
    protected static \UnitEnum|string|null $navigationGroup = 'کیفیت داده و منابع';

    public static function form(Schema $schema): Schema
    {
        return EventFieldOverrideForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EventFieldOverrideTable::configure($table);
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
            'index' => ListEventFieldOverrides::route('/'),
            'create' => CreateEventFieldOverride::route('/create'),
            'edit' => EditEventFieldOverride::route('/{record}/edit'),
        ];
    }
}

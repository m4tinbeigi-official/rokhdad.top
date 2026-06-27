<?php

namespace App\Filament\Resources\EventSourceApiKeys;

use App\Filament\Resources\EventSourceApiKeys\Pages\CreateEventSourceApiKey;
use App\Filament\Resources\EventSourceApiKeys\Pages\EditEventSourceApiKey;
use App\Filament\Resources\EventSourceApiKeys\Pages\ListEventSourceApiKeies;
use App\Filament\Resources\EventSourceApiKeys\Schemas\EventSourceApiKeyForm;
use App\Filament\Resources\EventSourceApiKeys\Tables\EventSourceApiKeyTable;
use App\Models\EventSourceApiKey;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EventSourceApiKeyResource extends Resource
{
    protected static ?string $model = EventSourceApiKey::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'کلید API منبع';
    protected static ?string $pluralModelLabel = 'کلیدهای API منبع';
    protected static ?string $navigationLabel = 'کلیدهای API منبع';
    protected static \UnitEnum|string|null $navigationGroup = 'کیفیت داده و منابع';

    public static function form(Schema $schema): Schema
    {
        return EventSourceApiKeyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EventSourceApiKeyTable::configure($table);
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
            'index' => ListEventSourceApiKeies::route('/'),
            'create' => CreateEventSourceApiKey::route('/create'),
            'edit' => EditEventSourceApiKey::route('/{record}/edit'),
        ];
    }
}

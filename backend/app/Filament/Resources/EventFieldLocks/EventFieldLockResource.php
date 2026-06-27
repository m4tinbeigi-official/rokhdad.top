<?php

namespace App\Filament\Resources\EventFieldLocks;

use App\Filament\Resources\EventFieldLocks\Pages\CreateEventFieldLock;
use App\Filament\Resources\EventFieldLocks\Pages\EditEventFieldLock;
use App\Filament\Resources\EventFieldLocks\Pages\ListEventFieldLocks;
use App\Filament\Resources\EventFieldLocks\Schemas\EventFieldLockForm;
use App\Filament\Resources\EventFieldLocks\Tables\EventFieldLockTable;
use App\Models\EventFieldLock;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EventFieldLockResource extends Resource
{
    protected static ?string $model = EventFieldLock::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'قفل فیلد';
    protected static ?string $pluralModelLabel = 'قفل فیلدها';
    protected static ?string $navigationLabel = 'قفل فیلد رویداد';
    protected static \UnitEnum|string|null $navigationGroup = 'کیفیت داده و منابع';

    public static function form(Schema $schema): Schema
    {
        return EventFieldLockForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EventFieldLockTable::configure($table);
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
            'index' => ListEventFieldLocks::route('/'),
            'create' => CreateEventFieldLock::route('/create'),
            'edit' => EditEventFieldLock::route('/{record}/edit'),
        ];
    }
}

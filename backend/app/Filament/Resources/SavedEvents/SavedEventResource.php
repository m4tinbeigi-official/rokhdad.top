<?php

namespace App\Filament\Resources\SavedEvents;

use App\Filament\Resources\SavedEvents\Pages\CreateSavedEvent;
use App\Filament\Resources\SavedEvents\Pages\EditSavedEvent;
use App\Filament\Resources\SavedEvents\Pages\ListSavedEvents;
use App\Filament\Resources\SavedEvents\Schemas\SavedEventForm;
use App\Filament\Resources\SavedEvents\Tables\SavedEventTable;
use App\Models\SavedEvent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SavedEventResource extends Resource
{
    protected static ?string $model = SavedEvent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'رویداد ذخیره‌شده';
    protected static ?string $pluralModelLabel = 'رویدادهای ذخیره‌شده';
    protected static ?string $navigationLabel = 'رویدادهای ذخیره‌شده';
    protected static \UnitEnum|string|null $navigationGroup = 'محتوا';

    public static function form(Schema $schema): Schema
    {
        return SavedEventForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SavedEventTable::configure($table);
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
            'index' => ListSavedEvents::route('/'),
            'create' => CreateSavedEvent::route('/create'),
            'edit' => EditSavedEvent::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources\EventTicketTypes;

use App\Filament\Resources\EventTicketTypes\Pages\CreateEventTicketType;
use App\Filament\Resources\EventTicketTypes\Pages\EditEventTicketType;
use App\Filament\Resources\EventTicketTypes\Pages\ListEventTicketTypes;
use App\Filament\Resources\EventTicketTypes\Schemas\EventTicketTypeForm;
use App\Filament\Resources\EventTicketTypes\Tables\EventTicketTypeTable;
use App\Models\EventTicketType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EventTicketTypeResource extends Resource
{
    protected static ?string $model = EventTicketType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'نوع بلیت';
    protected static ?string $pluralModelLabel = 'انواع بلیت';
    protected static ?string $navigationLabel = 'انواع بلیت';
    protected static \UnitEnum|string|null $navigationGroup = 'فروش و مالی';

    public static function form(Schema $schema): Schema
    {
        return EventTicketTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EventTicketTypeTable::configure($table);
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
            'index' => ListEventTicketTypes::route('/'),
            'create' => CreateEventTicketType::route('/create'),
            'edit' => EditEventTicketType::route('/{record}/edit'),
        ];
    }
}

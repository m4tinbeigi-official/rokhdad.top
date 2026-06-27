<?php

namespace App\Filament\Resources\EventPromoCodes;

use App\Filament\Resources\EventPromoCodes\Pages\CreateEventPromoCode;
use App\Filament\Resources\EventPromoCodes\Pages\EditEventPromoCode;
use App\Filament\Resources\EventPromoCodes\Pages\ListEventPromoCodes;
use App\Filament\Resources\EventPromoCodes\Schemas\EventPromoCodeForm;
use App\Filament\Resources\EventPromoCodes\Tables\EventPromoCodeTable;
use App\Models\EventPromoCode;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EventPromoCodeResource extends Resource
{
    protected static ?string $model = EventPromoCode::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'کد تخفیف';
    protected static ?string $pluralModelLabel = 'کدهای تخفیف';
    protected static ?string $navigationLabel = 'کدهای تخفیف';
    protected static \UnitEnum|string|null $navigationGroup = 'فروش و مالی';

    public static function form(Schema $schema): Schema
    {
        return EventPromoCodeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EventPromoCodeTable::configure($table);
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
            'index' => ListEventPromoCodes::route('/'),
            'create' => CreateEventPromoCode::route('/create'),
            'edit' => EditEventPromoCode::route('/{record}/edit'),
        ];
    }
}

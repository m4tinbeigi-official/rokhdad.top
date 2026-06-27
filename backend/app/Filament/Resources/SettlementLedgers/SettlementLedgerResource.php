<?php

namespace App\Filament\Resources\SettlementLedgers;

use App\Filament\Resources\SettlementLedgers\Pages\CreateSettlementLedger;
use App\Filament\Resources\SettlementLedgers\Pages\EditSettlementLedger;
use App\Filament\Resources\SettlementLedgers\Pages\ListSettlementLedgers;
use App\Filament\Resources\SettlementLedgers\Schemas\SettlementLedgerForm;
use App\Filament\Resources\SettlementLedgers\Tables\SettlementLedgerTable;
use App\Models\SettlementLedger;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SettlementLedgerResource extends Resource
{
    protected static ?string $model = SettlementLedger::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'دفتر تسویه';
    protected static ?string $pluralModelLabel = 'دفتر تسویه';
    protected static ?string $navigationLabel = 'دفتر تسویه';
    protected static \UnitEnum|string|null $navigationGroup = 'فروش و مالی';

    public static function form(Schema $schema): Schema
    {
        return SettlementLedgerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SettlementLedgerTable::configure($table);
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
            'index' => ListSettlementLedgers::route('/'),
            'create' => CreateSettlementLedger::route('/create'),
            'edit' => EditSettlementLedger::route('/{record}/edit'),
        ];
    }
}

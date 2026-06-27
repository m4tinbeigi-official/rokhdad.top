<?php

namespace App\Filament\Resources\OtpCodes;

use App\Filament\Resources\OtpCodes\Pages\CreateOtpCode;
use App\Filament\Resources\OtpCodes\Pages\EditOtpCode;
use App\Filament\Resources\OtpCodes\Pages\ListOtpCodes;
use App\Filament\Resources\OtpCodes\Schemas\OtpCodeForm;
use App\Filament\Resources\OtpCodes\Tables\OtpCodeTable;
use App\Models\OtpCode;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class OtpCodeResource extends Resource
{
    protected static ?string $model = OtpCode::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'کد یک‌بارمصرف';
    protected static ?string $pluralModelLabel = 'کدهای یک‌بارمصرف';
    protected static ?string $navigationLabel = 'کدهای OTP';
    protected static \UnitEnum|string|null $navigationGroup = 'کمپین و اطلاع‌رسانی';

    public static function form(Schema $schema): Schema
    {
        return OtpCodeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OtpCodeTable::configure($table);
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
            'index' => ListOtpCodes::route('/'),
            'create' => CreateOtpCode::route('/create'),
            'edit' => EditOtpCode::route('/{record}/edit'),
        ];
    }
}

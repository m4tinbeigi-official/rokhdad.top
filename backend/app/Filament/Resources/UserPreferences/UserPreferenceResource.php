<?php

namespace App\Filament\Resources\UserPreferences;

use App\Filament\Resources\UserPreferences\Pages\CreateUserPreference;
use App\Filament\Resources\UserPreferences\Pages\EditUserPreference;
use App\Filament\Resources\UserPreferences\Pages\ListUserPreferences;
use App\Filament\Resources\UserPreferences\Schemas\UserPreferenceForm;
use App\Filament\Resources\UserPreferences\Tables\UserPreferenceTable;
use App\Models\UserPreference;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UserPreferenceResource extends Resource
{
    protected static ?string $model = UserPreference::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'تنظیمات کاربر';
    protected static ?string $pluralModelLabel = 'تنظیمات کاربران';
    protected static ?string $navigationLabel = 'تنظیمات کاربر';
    protected static \UnitEnum|string|null $navigationGroup = 'دسترسی و کاربران';

    public static function form(Schema $schema): Schema
    {
        return UserPreferenceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UserPreferenceTable::configure($table);
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
            'index' => ListUserPreferences::route('/'),
            'create' => CreateUserPreference::route('/create'),
            'edit' => EditUserPreference::route('/{record}/edit'),
        ];
    }
}

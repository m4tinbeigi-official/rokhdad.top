<?php

namespace App\Filament\Resources\HermesErrors;

use App\Filament\Resources\HermesErrors\Pages\ListHermesErrors;
use App\Filament\Resources\HermesErrors\Schemas\HermesErrorForm;
use App\Filament\Resources\HermesErrors\Tables\HermesErrorsTable;
use App\Models\HermesError;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class HermesErrorResource extends Resource
{
    protected static ?string $model = HermesError::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'خطای Hermes';
    protected static ?string $pluralModelLabel = 'خطاهای Hermes';
    protected static ?string $navigationLabel = 'خطاهای Hermes';
    protected static \UnitEnum|string|null $navigationGroup = 'سیستم';

    public static function form(Schema $schema): Schema
    {
        return HermesErrorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HermesErrorsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHermesErrors::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        if (! config('hermes.enabled')) {
            return false;
        }

        $user = auth()->user();

        return $user !== null && method_exists($user, 'hasRole') && $user->hasRole('admin');
    }
}

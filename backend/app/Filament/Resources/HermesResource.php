<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HermesResource\Pages;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class HermesResource extends Resource
{
    protected static ?string $model = null; // No underlying Eloquent model

    public static function form(Schema $schema): Schema
    {
        // Not used – pages define their own forms.
        return $schema;
    }

    public static function table(Table $table): Table
    {
        // No table view needed.
        return $table;
    }

    public static function getPages(): array
    {
        return [
            'manage' => Pages\ManageHermes::route('/'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return 'Hermes';
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-rocket';
    }

    /**
     * Hermes is developer/admin tooling — restrict it to admins.
     */
    public static function canAccess(): bool
    {
        if (! config('hermes.enabled')) {
            return false;
        }

        $user = auth()->user();

        return $user !== null && method_exists($user, 'hasRole') && $user->hasRole('admin');
    }
}

<?php

namespace App\Filament\Resources\WebhookDeliveries;

use App\Filament\Resources\WebhookDeliveries\Pages\CreateWebhookDelivery;
use App\Filament\Resources\WebhookDeliveries\Pages\EditWebhookDelivery;
use App\Filament\Resources\WebhookDeliveries\Pages\ListWebhookDeliveries;
use App\Filament\Resources\WebhookDeliveries\Schemas\WebhookDeliveryForm;
use App\Filament\Resources\WebhookDeliveries\Tables\WebhookDeliveryTable;
use App\Models\WebhookDelivery;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WebhookDeliveryResource extends Resource
{
    protected static ?string $model = WebhookDelivery::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'تحویل وب‌هوک';
    protected static ?string $pluralModelLabel = 'تحویل‌های وب‌هوک';
    protected static ?string $navigationLabel = 'تحویل وب‌هوک';
    protected static \UnitEnum|string|null $navigationGroup = 'وب‌هوک';

    public static function form(Schema $schema): Schema
    {
        return WebhookDeliveryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WebhookDeliveryTable::configure($table);
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
            'index' => ListWebhookDeliveries::route('/'),
            'create' => CreateWebhookDelivery::route('/create'),
            'edit' => EditWebhookDelivery::route('/{record}/edit'),
        ];
    }
}

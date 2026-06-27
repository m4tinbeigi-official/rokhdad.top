<?php

namespace App\Filament\Resources\CampaignMessages;

use App\Filament\Resources\CampaignMessages\Pages\CreateCampaignMessage;
use App\Filament\Resources\CampaignMessages\Pages\EditCampaignMessage;
use App\Filament\Resources\CampaignMessages\Pages\ListCampaignMessages;
use App\Filament\Resources\CampaignMessages\Schemas\CampaignMessageForm;
use App\Filament\Resources\CampaignMessages\Tables\CampaignMessageTable;
use App\Models\CampaignMessage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CampaignMessageResource extends Resource
{
    protected static ?string $model = CampaignMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'پیام کمپین';
    protected static ?string $pluralModelLabel = 'پیام‌های کمپین';
    protected static ?string $navigationLabel = 'پیام‌های کمپین';
    protected static \UnitEnum|string|null $navigationGroup = 'کمپین و اطلاع‌رسانی';

    public static function form(Schema $schema): Schema
    {
        return CampaignMessageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CampaignMessageTable::configure($table);
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
            'index' => ListCampaignMessages::route('/'),
            'create' => CreateCampaignMessage::route('/create'),
            'edit' => EditCampaignMessage::route('/{record}/edit'),
        ];
    }
}

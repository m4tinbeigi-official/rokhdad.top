<?php

namespace App\Filament\Resources\CampaignAnalytics;

use App\Filament\Resources\CampaignAnalytics\Pages\CreateCampaignAnalytic;
use App\Filament\Resources\CampaignAnalytics\Pages\EditCampaignAnalytic;
use App\Filament\Resources\CampaignAnalytics\Pages\ListCampaignAnalytics;
use App\Filament\Resources\CampaignAnalytics\Schemas\CampaignAnalyticForm;
use App\Filament\Resources\CampaignAnalytics\Tables\CampaignAnalyticTable;
use App\Models\CampaignAnalytics;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CampaignAnalyticResource extends Resource
{
    protected static ?string $model = CampaignAnalytics::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'آمار کمپین';
    protected static ?string $pluralModelLabel = 'آمار کمپین‌ها';
    protected static ?string $navigationLabel = 'آمار کمپین';
    protected static \UnitEnum|string|null $navigationGroup = 'کمپین و اطلاع‌رسانی';

    public static function form(Schema $schema): Schema
    {
        return CampaignAnalyticForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CampaignAnalyticTable::configure($table);
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
            'index' => ListCampaignAnalytics::route('/'),
            'create' => CreateCampaignAnalytic::route('/create'),
            'edit' => EditCampaignAnalytic::route('/{record}/edit'),
        ];
    }
}

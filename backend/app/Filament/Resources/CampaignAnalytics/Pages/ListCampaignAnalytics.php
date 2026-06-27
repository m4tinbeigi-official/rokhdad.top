<?php

namespace App\Filament\Resources\CampaignAnalytics\Pages;

use App\Filament\Resources\CampaignAnalytics\CampaignAnalyticResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCampaignAnalytics extends ListRecords
{
    protected static string $resource = CampaignAnalyticResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\CampaignAnalytics\Pages;

use App\Filament\Resources\CampaignAnalytics\CampaignAnalyticResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCampaignAnalytic extends EditRecord
{
    protected static string $resource = CampaignAnalyticResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

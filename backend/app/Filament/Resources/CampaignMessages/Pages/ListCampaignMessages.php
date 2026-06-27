<?php

namespace App\Filament\Resources\CampaignMessages\Pages;

use App\Filament\Resources\CampaignMessages\CampaignMessageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCampaignMessages extends ListRecords
{
    protected static string $resource = CampaignMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

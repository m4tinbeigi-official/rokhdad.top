<?php

namespace App\Filament\Resources\CampaignMessages\Pages;

use App\Filament\Resources\CampaignMessages\CampaignMessageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCampaignMessage extends EditRecord
{
    protected static string $resource = CampaignMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

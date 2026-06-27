<?php

namespace App\Filament\Resources\EventFieldOverrides\Pages;

use App\Filament\Resources\EventFieldOverrides\EventFieldOverrideResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEventFieldOverride extends EditRecord
{
    protected static string $resource = EventFieldOverrideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

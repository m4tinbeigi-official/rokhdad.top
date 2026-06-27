<?php

namespace App\Filament\Resources\EventSourceAttributions\Pages;

use App\Filament\Resources\EventSourceAttributions\EventSourceAttributionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEventSourceAttribution extends EditRecord
{
    protected static string $resource = EventSourceAttributionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

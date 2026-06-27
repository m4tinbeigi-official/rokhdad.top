<?php

namespace App\Filament\Resources\EventSourceApiKeys\Pages;

use App\Filament\Resources\EventSourceApiKeys\EventSourceApiKeyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEventSourceApiKeies extends ListRecords
{
    protected static string $resource = EventSourceApiKeyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

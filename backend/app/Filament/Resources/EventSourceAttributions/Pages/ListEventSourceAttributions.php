<?php

namespace App\Filament\Resources\EventSourceAttributions\Pages;

use App\Filament\Resources\EventSourceAttributions\EventSourceAttributionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEventSourceAttributions extends ListRecords
{
    protected static string $resource = EventSourceAttributionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

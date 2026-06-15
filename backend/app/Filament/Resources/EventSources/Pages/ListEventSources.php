<?php

namespace App\Filament\Resources\EventSources\Pages;

use App\Filament\Resources\EventSources\EventSourceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEventSources extends ListRecords
{
    protected static string $resource = EventSourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

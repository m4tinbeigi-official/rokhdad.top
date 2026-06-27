<?php

namespace App\Filament\Resources\EventFieldOverrides\Pages;

use App\Filament\Resources\EventFieldOverrides\EventFieldOverrideResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEventFieldOverrides extends ListRecords
{
    protected static string $resource = EventFieldOverrideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

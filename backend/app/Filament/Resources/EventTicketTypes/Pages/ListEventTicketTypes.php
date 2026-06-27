<?php

namespace App\Filament\Resources\EventTicketTypes\Pages;

use App\Filament\Resources\EventTicketTypes\EventTicketTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEventTicketTypes extends ListRecords
{
    protected static string $resource = EventTicketTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\EventTicketTypes\Pages;

use App\Filament\Resources\EventTicketTypes\EventTicketTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEventTicketType extends EditRecord
{
    protected static string $resource = EventTicketTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\EventTicketTypes\Pages;

use App\Filament\Resources\EventTicketTypes\EventTicketTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEventTicketType extends CreateRecord
{
    protected static string $resource = EventTicketTypeResource::class;
}

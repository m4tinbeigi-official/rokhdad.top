<?php

namespace App\Filament\Resources\EventSources\Pages;

use App\Filament\Resources\EventSources\EventSourceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEventSource extends CreateRecord
{
    protected static string $resource = EventSourceResource::class;
}

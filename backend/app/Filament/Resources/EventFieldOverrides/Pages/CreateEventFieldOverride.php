<?php

namespace App\Filament\Resources\EventFieldOverrides\Pages;

use App\Filament\Resources\EventFieldOverrides\EventFieldOverrideResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEventFieldOverride extends CreateRecord
{
    protected static string $resource = EventFieldOverrideResource::class;
}

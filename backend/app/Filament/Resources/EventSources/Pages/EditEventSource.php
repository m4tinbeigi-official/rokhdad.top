<?php

namespace App\Filament\Resources\EventSources\Pages;

use App\Filament\Resources\EventSources\EventSourceResource;
use Filament\Resources\Pages\EditRecord;

class EditEventSource extends EditRecord
{
    protected static string $resource = EventSourceResource::class;
}

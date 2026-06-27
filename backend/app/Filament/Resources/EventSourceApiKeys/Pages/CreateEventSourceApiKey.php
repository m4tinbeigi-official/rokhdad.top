<?php

namespace App\Filament\Resources\EventSourceApiKeys\Pages;

use App\Filament\Resources\EventSourceApiKeys\EventSourceApiKeyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEventSourceApiKey extends CreateRecord
{
    protected static string $resource = EventSourceApiKeyResource::class;
}

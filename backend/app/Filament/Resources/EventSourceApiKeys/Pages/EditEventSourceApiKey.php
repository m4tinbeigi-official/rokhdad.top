<?php

namespace App\Filament\Resources\EventSourceApiKeys\Pages;

use App\Filament\Resources\EventSourceApiKeys\EventSourceApiKeyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEventSourceApiKey extends EditRecord
{
    protected static string $resource = EventSourceApiKeyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

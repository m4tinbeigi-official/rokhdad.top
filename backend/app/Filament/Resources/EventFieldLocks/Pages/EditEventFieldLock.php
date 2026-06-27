<?php

namespace App\Filament\Resources\EventFieldLocks\Pages;

use App\Filament\Resources\EventFieldLocks\EventFieldLockResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEventFieldLock extends EditRecord
{
    protected static string $resource = EventFieldLockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\EventFieldLocks\Pages;

use App\Filament\Resources\EventFieldLocks\EventFieldLockResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEventFieldLocks extends ListRecords
{
    protected static string $resource = EventFieldLockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

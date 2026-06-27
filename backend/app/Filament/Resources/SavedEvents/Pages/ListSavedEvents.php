<?php

namespace App\Filament\Resources\SavedEvents\Pages;

use App\Filament\Resources\SavedEvents\SavedEventResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSavedEvents extends ListRecords
{
    protected static string $resource = SavedEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

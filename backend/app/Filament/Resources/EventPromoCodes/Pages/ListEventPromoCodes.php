<?php

namespace App\Filament\Resources\EventPromoCodes\Pages;

use App\Filament\Resources\EventPromoCodes\EventPromoCodeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEventPromoCodes extends ListRecords
{
    protected static string $resource = EventPromoCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

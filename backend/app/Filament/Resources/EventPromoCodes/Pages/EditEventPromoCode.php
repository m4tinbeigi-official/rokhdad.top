<?php

namespace App\Filament\Resources\EventPromoCodes\Pages;

use App\Filament\Resources\EventPromoCodes\EventPromoCodeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEventPromoCode extends EditRecord
{
    protected static string $resource = EventPromoCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

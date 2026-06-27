<?php

namespace App\Filament\Resources\SavedEvents\Pages;

use App\Filament\Resources\SavedEvents\SavedEventResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSavedEvent extends EditRecord
{
    protected static string $resource = SavedEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

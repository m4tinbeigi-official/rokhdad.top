<?php

namespace App\Filament\Resources\SavedEvents\Pages;

use App\Filament\Resources\SavedEvents\SavedEventResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSavedEvent extends CreateRecord
{
    protected static string $resource = SavedEventResource::class;
}

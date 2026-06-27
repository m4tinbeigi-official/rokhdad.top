<?php

namespace App\Filament\Resources\UserPreferences\Pages;

use App\Filament\Resources\UserPreferences\UserPreferenceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUserPreference extends CreateRecord
{
    protected static string $resource = UserPreferenceResource::class;
}

<?php

namespace App\Filament\Resources\OtpCodes\Pages;

use App\Filament\Resources\OtpCodes\OtpCodeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOtpCodes extends ListRecords
{
    protected static string $resource = OtpCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

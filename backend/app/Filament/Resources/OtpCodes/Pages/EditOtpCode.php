<?php

namespace App\Filament\Resources\OtpCodes\Pages;

use App\Filament\Resources\OtpCodes\OtpCodeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOtpCode extends EditRecord
{
    protected static string $resource = OtpCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

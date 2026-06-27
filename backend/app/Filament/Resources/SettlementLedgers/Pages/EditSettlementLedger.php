<?php

namespace App\Filament\Resources\SettlementLedgers\Pages;

use App\Filament\Resources\SettlementLedgers\SettlementLedgerResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSettlementLedger extends EditRecord
{
    protected static string $resource = SettlementLedgerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\SettlementLedgers\Pages;

use App\Filament\Resources\SettlementLedgers\SettlementLedgerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSettlementLedgers extends ListRecords
{
    protected static string $resource = SettlementLedgerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

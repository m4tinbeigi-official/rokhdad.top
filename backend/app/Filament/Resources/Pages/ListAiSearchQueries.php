<?php

namespace App\Filament\Resources\Pages;

use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\AiSearchQueryResource;

class ListAiSearchQueries extends ListRecords
{
    protected static string $resource = AiSearchQueryResource::class;
}

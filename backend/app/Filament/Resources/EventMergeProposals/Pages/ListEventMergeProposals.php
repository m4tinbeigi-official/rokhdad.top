<?php

namespace App\Filament\Resources\EventMergeProposals\Pages;

use App\Filament\Resources\EventMergeProposals\EventMergeProposalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;
use Filament\Notifications\Notification;

class ListEventMergeProposals extends ListRecords
{
    protected static string $resource = EventMergeProposalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('scan')
                ->label('جستجوی هوشمند رویدادهای تکراری')
                ->icon('heroicon-o-cpu-chip')
                ->color('primary')
                ->action(function () {
                    Artisan::call('ai:deduplicate-events', ['--limit' => 10]);
                    
                    Notification::make()
                        ->title('جستجو انجام شد')
                        ->body(Artisan::output())
                        ->success()
                        ->send();
                }),
        ];
    }
}

<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class TaskManagement extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationLabel = 'Task Management';
    protected static ?string $navigationIcon = 'heroicon-o-collection';
    protected static string $view = 'filament.pages.task-management'; // optional custom view

    public function getTitle(): string
    {
        return 'Server Task Management';
    }

    /**
     * Build query for jobs (including failed jobs as separate rows).
     */
    protected function getTableQuery()
    {
        // Union jobs and failed_jobs with a type column
        $jobs = DB::table('jobs')
            ->select(
                'id',
                'queue',
                'payload',
                'attempts',
                DB::raw('"running" as status'),
                'created_at'
            );
        $failed = DB::table('failed_jobs')
            ->select(
                'id',
                'queue',
                'payload',
                DB::raw('0 as attempts'),
                DB::raw('"failed" as status'),
                'failed_at as created_at'
            );
        return $jobs->unionAll($failed);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('id')->label('ID')->searchable(),
            TextColumn::make('queue')->label('Queue')->searchable(),
            TextColumn::make('status')->label('Status')->badge(),
            TextColumn::make('attempts')->label('Attempts'),
            TextColumn::make('created_at')->label('Created At')->dateTime(),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Action::make('stop')
                ->label('Stop')
                ->icon('heroicon-s-trash')
                ->color('danger')
                ->action(function (array $record) {
                    if ($record['status'] === 'running') {
                        DB::table('jobs')->where('id', $record['id'])->delete();
                    } else {
                        DB::table('failed_jobs')->where('id', $record['id'])->delete();
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('Confirm stop')
                ->modalSubheading('Are you sure you want to stop this task?'),
            Action::make('retry')
                ->label('Retry')
                ->icon('heroicon-s-arrow-path')
                ->color('primary')
                ->action(function (array $record) {
                    if ($record['status'] === 'failed') {
                        // move back to jobs table
                        DB::table('jobs')->insert([
                            'queue' => $record['queue'],
                            'payload' => $record['payload'],
                            'attempts' => 0,
                            'reserved_at' => null,
                            'available_at' => now()->timestamp,
                            'created_at' => now(),
                        ]);
                        DB::table('failed_jobs')->where('id', $record['id'])->delete();
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('Confirm retry')
                ->modalSubheading('Retry this failed task?')
                ->visible(fn (array $record) => $record['status'] === 'failed'),
        ];
    }
}

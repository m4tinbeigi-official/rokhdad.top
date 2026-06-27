<?php

namespace App\Filament\Resources\EventMergeProposals;

use App\Filament\Resources\EventMergeProposals\Pages\ListEventMergeProposals;
use App\Models\EventMergeProposal;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use App\Models\EventSourceAttribution;

class EventMergeProposalResource extends Resource
{
    protected static ?string $model = EventMergeProposal::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-sparkles';
    
    protected static \UnitEnum|string|null $navigationGroup = 'هوش مصنوعی';
    protected static ?string $modelLabel = 'پیشنهاد ادغام';
    protected static ?string $pluralModelLabel = 'ادغام رویدادها (AI)';
    protected static ?string $navigationLabel = 'ادغام رویدادها (AI)';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // We won't really use the form for editing since it's just AI proposals
                Forms\Components\TextInput::make('confidence_score')
                    ->label('میزان اطمینان')
                    ->disabled(),
                Forms\Components\Textarea::make('ai_reasoning')
                    ->label('استدلال هوش مصنوعی')
                    ->disabled()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('primaryEvent.title')
                    ->label('رویداد اصلی')
                    ->description(fn (EventMergeProposal $record): string => 'ID: ' . $record->primary_event_id)
                    ->url(fn (EventMergeProposal $record): string => route('filament.admin.resources.events.edit', ['record' => $record->primary_event_id]))
                    ->wrap(),
                Tables\Columns\TextColumn::make('duplicateEvent.title')
                    ->label('رویداد تکراری')
                    ->description(fn (EventMergeProposal $record): string => 'ID: ' . $record->duplicate_event_id)
                    ->url(fn (EventMergeProposal $record): string => route('filament.admin.resources.events.edit', ['record' => $record->duplicate_event_id]))
                    ->wrap(),
                Tables\Columns\TextColumn::make('confidence_score')
                    ->label('اطمینان')
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        $state >= 90 => 'success',
                        $state >= 75 => 'warning',
                        default => 'danger',
                    })
                    ->suffix('%'),
                Tables\Columns\TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'در انتظار بررسی',
                        'approved' => 'تایید شده',
                        'rejected' => 'رد شده',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options([
                        'pending' => 'در انتظار بررسی',
                        'approved' => 'تایید شده',
                        'rejected' => 'رد شده',
                    ])
                    ->default('pending'),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('تایید ادغام')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('تایید ادغام دو رویداد')
                    ->modalDescription('آیا مطمئن هستید؟ رویداد تکراری حذف شده و لینک‌های منبع آن به رویداد اصلی منتقل می‌شود.')
                    ->visible(fn (EventMergeProposal $record): bool => $record->status === 'pending')
                    ->action(function (EventMergeProposal $record) {
                        // Move source attributions
                        EventSourceAttribution::where('event_id', $record->duplicate_event_id)
                            ->update(['event_id' => $record->primary_event_id]);
                        
                        // Delete the duplicate event
                        $record->duplicateEvent()->delete();
                        
                        $record->update(['status' => 'approved']);
                        
                        Notification::make()
                            ->title('ادغام با موفقیت انجام شد')
                            ->success()
                            ->send();
                    }),
                    
                Tables\Actions\Action::make('reject')
                    ->label('رد پیشنهاد')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (EventMergeProposal $record): bool => $record->status === 'pending')
                    ->action(function (EventMergeProposal $record) {
                        $record->update(['status' => 'rejected']);
                        
                        Notification::make()
                            ->title('پیشنهاد رد شد')
                            ->info()
                            ->send();
                    }),
                    
                Tables\Actions\ViewAction::make()->label('جزئیات استدلال'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEventMergeProposals::route('/'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Models\AiSearchQuery;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class AiSearchQueryResource extends Resource
{
    protected static ?string $model = AiSearchQuery::class;

    // protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static ?string $navigationLabel = 'AI Query Logs';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('user_query')
                ->required()
                ->maxLength(255),
            KeyValue::make('extracted_filters')
                ->keyLabel('Filter')
                ->valueLabel('Value'),
            TextInput::make('usage_count')
                ->numeric()
                ->minValue(1)
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user_query')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('usage_count')
                    ->label('Usage')
                    ->badge()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('created_between')
                    ->form([
                        DatePicker::make('created_from'),
                        DatePicker::make('created_until'),
                    ])
                    ->query(fn ($query, $data) => $query
                        ->when($data['created_from'], fn ($q) => $q->whereDate('created_at', '>=', $data['created_from']))
                        ->when($data['created_until'], fn ($q) => $q->whereDate('created_at', '<=', $data['created_until']))
                    ),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\Pages\ListAiSearchQueries::route('/'),
        ];
    }
}

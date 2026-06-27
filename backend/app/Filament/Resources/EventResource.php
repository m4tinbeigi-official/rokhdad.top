<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EventResource\Pages;
use App\Models\Event;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-calendar';
    
    protected static ?string $modelLabel = 'رویداد';
    protected static ?string $pluralModelLabel = 'رویدادها';
    protected static ?string $navigationLabel = 'رویدادها';
    protected static \UnitEnum|string|null $navigationGroup = 'مدیریت رویدادها';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('title')
                    ->label('عنوان رویداد')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('summary')
                    ->label('خلاصه')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('starts_at')
                    ->label('تاریخ شروع')
                    ->required(),
                Forms\Components\DateTimePicker::make('ends_at')
                    ->label('تاریخ پایان')
                    ->required(),
                Forms\Components\Select::make('organizer_id')
                    ->label('برگزارکننده')
                    ->relationship('organizer', 'name'),
                Forms\Components\Toggle::make('is_featured')
                    ->label('ویژه')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان')
                    ->searchable(),
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('تاریخ شروع')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('organizer.name')
                    ->label('برگزارکننده')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_featured')
                    ->label('ویژه')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('ویرایش'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('حذف انتخاب‌شده‌ها'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources\RequestLogs;

use App\Filament\Resources\RequestLogs\Schemas\RequestLogTable;
use App\Models\RequestLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RequestLogResource extends Resource
{
    protected static ?string $model = RequestLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentMagnifyingGlass;

    protected static ?string $navigationLabel = 'لاگ‌های درخواست';

    protected static \UnitEnum|string|null $navigationGroup = 'تنظیمات سیستم';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'لاگ درخواست';

    protected static ?string $pluralModelLabel = 'لاگ‌های درخواست';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return RequestLogTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRequestLogs::route('/'),
        ];
    }

    /** فقط نمایش — بدون ایجاد یا ویرایش */
    public static function canCreate(): bool
    {
        return false;
    }
}

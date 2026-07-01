<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Resources\AuditLogs\Schemas\AuditLogTable;
use App\Models\AuditLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $navigationLabel = 'گزارش حسابرسی';

    protected static \UnitEnum|string|null $navigationGroup = 'تنظیمات سیستم';

    protected static ?int $navigationSort = 21;

    protected static ?string $modelLabel = 'رویداد حسابرسی';

    protected static ?string $pluralModelLabel = 'گزارش حسابرسی';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return AuditLogTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
        ];
    }

    /** فقط نمایش — رویدادهای حسابرسی نباید ایجاد، ویرایش یا حذف شوند. */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}

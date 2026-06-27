<?php

namespace App\Filament\Resources\OtpCodes\Schemas;

use Filament\Schemas\Schema;

class OtpCodeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\TextInput::make('phone')
                    ->label('شماره تلفن'),
                \Filament\Forms\Components\TextInput::make('code')
                    ->label('کد'),
                \Filament\Forms\Components\TextInput::make('purpose')
                    ->label('هدف'),
                \Filament\Forms\Components\TextInput::make('attempts')
                    ->label('تعداد تلاش')
                    ->numeric(),
                \Filament\Forms\Components\Toggle::make('used')
                    ->label('استفاده‌شده'),
                \Filament\Forms\Components\DateTimePicker::make('expires_at')
                    ->label('انقضا'),
            ]);
    }
}

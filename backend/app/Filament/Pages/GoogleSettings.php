<?php

namespace App\Filament\Pages;

use App\Models\GoogleSetting;
use App\Services\GoogleClientService;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class GoogleSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationLabel = 'تنظیمات گوگل';
    protected static ?string $title = 'تنظیمات اتصال به گوگل';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static \UnitEnum|string|null $navigationGroup = 'تنظیمات سیستم';

    protected string $view = 'filament.pages.google-settings';

    public ?array $data = [];
    public bool $isConnected = false;

    public function mount(): void
    {
        $settings = GoogleSetting::getActive();
        $this->isConnected = $settings->isConnected();

        $this->form->fill([
            'client_id' => $settings->client_id,
            'client_secret' => $settings->client_secret,
            'redirect_uri' => $settings->redirect_uri ?: url('/admin/google/callback'),
            'analytics_property_id' => $settings->analytics_property_id,
            'search_console_site_url' => $settings->search_console_site_url,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('اطلاعات پروژه در گوگل کلود')
                    ->description('شناسه و رمز برنامه OAuth 2.0 خود را از کنسول گوگل کلود دریافت و در اینجا وارد کنید.')
                    ->schema([
                        TextInput::make('client_id')
                            ->label('Client ID')
                            ->placeholder('مثال: 123456789-abc.apps.googleusercontent.com')
                            ->required(),
                        TextInput::make('client_secret')
                            ->label('Client Secret')
                            ->password()
                            ->revealable()
                            ->placeholder('رمز مخفی کلاینت را وارد کنید')
                            ->required(),
                        TextInput::make('redirect_uri')
                            ->label('Redirect URI')
                            ->helperText('این آدرس را باید در کنسول گوگل کلود به عنوان Authorized redirect URIs ثبت کنید.')
                            ->readonly()
                            ->required(),
                    ])->columns(1),

                Section::make('تنظیمات سرویس‌ها')
                    ->description('اطلاعات مربوط به پراپرتی گوگل آنالیتیکس و آدرس سایت در سرچ کنسول.')
                    ->schema([
                        TextInput::make('analytics_property_id')
                            ->label('شناسه پراپرتی آنالیتیکس (GA4 Property ID)')
                            ->placeholder('مثال: 412345678')
                            ->required(),
                        TextInput::make('search_console_site_url')
                            ->label('آدرس سایت در سرچ کنسول')
                            ->placeholder('مثال: https://rokhdad.top')
                            ->url()
                            ->required(),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('ذخیره تنظیمات')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $formData = $this->form->getState();

        $settings = GoogleSetting::getActive();
        $settings->update($formData);

        Notification::make()
            ->title('تنظیمات با موفقیت ذخیره شد.')
            ->success()
            ->send();

        // Refresh connection state
        $this->isConnected = $settings->isConnected();
    }

    /**
     * Start the authentication flow.
     */
    public function connectGoogle(GoogleClientService $clientService)
    {
        $settings = GoogleSetting::getActive();
        if (!$settings->hasCredentials()) {
            Notification::make()
                ->title('لطفا ابتدا Client ID و Client Secret را وارد و ذخیره کنید.')
                ->danger()
                ->send();
            return;
        }

        try {
            $url = $clientService->getAuthUrl();
            return redirect()->away($url);
        } catch (\Exception $e) {
            Notification::make()
                ->title('خطا در برقراری ارتباط با گوگل: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Disconnect/revoke Google credentials.
     */
    public function disconnectGoogle()
    {
        $settings = GoogleSetting::getActive();
        $settings->update([
            'access_token' => null,
            'refresh_token' => null,
            'token_type' => null,
            'expires_in' => null,
            'created_at_timestamp' => null,
        ]);

        $this->isConnected = false;

        Notification::make()
            ->title('اتصال حساب گوگل با موفقیت قطع شد.')
            ->success()
            ->send();
    }
}

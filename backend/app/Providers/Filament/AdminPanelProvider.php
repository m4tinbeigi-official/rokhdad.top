<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('رخداد')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->plugins([
                // \HusamTariq\FilamentDatabaseSchedule\FilamentDatabaseSchedulePlugin::make(),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->renderHook(
                \Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
                fn (): string => \Illuminate\Support\Facades\Blade::render('
                    <style>
                        /* Reset body background */
                        body { background: #0f172a !important; }
                        
                        /* Beautiful custom CSS */
                        main {
                            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%) !important;
                            position: relative;
                            overflow: hidden;
                        }
                        
                        /* Background glow effect */
                        main::before {
                            content: "";
                            position: absolute;
                            top: -50%;
                            left: -50%;
                            width: 200%;
                            height: 200%;
                            background: radial-gradient(circle, rgba(245, 158, 11, 0.15) 0%, rgba(0,0,0,0) 50%);
                            animation: pulse 15s infinite;
                            z-index: 0;
                            pointer-events: none;
                        }
                        
                        /* Hide the default filament logo since we have a custom one */
                        .fi-logo { display: none !important; }
                        
                        /* Make the main card glassmorphic */
                        .fi-simple-main-content {
                            backdrop-filter: blur(20px) !important;
                            -webkit-backdrop-filter: blur(20px) !important;
                            background-color: rgba(30, 41, 59, 0.6) !important;
                            border: 1px solid rgba(255, 255, 255, 0.1) !important;
                            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6) !important;
                            border-radius: 1.5rem !important;
                            padding: 2.5rem !important;
                            z-index: 10 !important;
                            position: relative !important;
                        }
                        
                        /* Custom styling for the submit button */
                        .fi-btn {
                            background: linear-gradient(90deg, #f59e0b, #ea580c) !important;
                            border: none !important;
                            transition: all 0.3s ease !important;
                            color: white !important;
                            box-shadow: 0 4px 6px -1px rgba(245, 158, 11, 0.2) !important;
                        }
                        
                        .fi-btn:hover {
                            transform: translateY(-2px) !important;
                            box-shadow: 0 10px 20px -10px rgba(245, 158, 11, 0.6) !important;
                        }
                        
                        @keyframes pulse {
                            0% { transform: scale(1); }
                            50% { transform: scale(1.1); }
                            100% { transform: scale(1); }
                        }
                        
                        /* Dark mode inputs styling */
                        .fi-input-wrapper {
                            background: rgba(15, 23, 42, 0.6) !important;
                            border-color: rgba(255, 255, 255, 0.1) !important;
                            transition: all 0.3s ease !important;
                        }
                        
                        .fi-input-wrapper:focus-within {
                            border-color: #f59e0b !important;
                            box-shadow: 0 0 0 1px #f59e0b !important;
                        }
                        
                        .fi-input {
                            color: #f8fafc !important;
                            background: transparent !important;
                        }
                        
                        label { color: #cbd5e1 !important; }
                        
                        /* Hide the default header */
                        header.fi-simple-header { display: none !important; }
                    </style>

                    <div style="text-align: center; margin-bottom: 2.5rem; z-index: 10; position: relative;">
                        <div style="display: inline-block; padding: 1rem; background: rgba(245, 158, 11, 0.1); border-radius: 50%; margin-bottom: 1rem; border: 1px solid rgba(245, 158, 11, 0.2);">
                            <svg style="width: 3rem; height: 3rem; color: #f59e0b;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6" />
                            </svg>
                        </div>
                        <h1 style="font-size: 2.5rem; font-weight: 800; background: -webkit-linear-gradient(#fcd34d, #f59e0b); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin: 0; line-height: 1.2;">رخداد تاپ</h1>
                        <p style="color: #94a3b8; margin-top: 0.75rem; font-size: 1.1rem; letter-spacing: 0.05em;">ورود به مدیریت سیستم</p>
                    </div>
                ')
            );
    }
}

<x-filament-panels::page.simple>
    <style>
        /* Base overlay */
        .fi-simple-page {
            position: relative;
        }
        .fi-simple-page::before {
            content: '';
            position: fixed;
            top: 0; left: 0; width: 100vw; height: 100vh;
            background-image: url('https://images.unsplash.com/photo-1540575467063-178a50c2df87?auto=format&fit=crop&q=80');
            background-size: cover;
            background-position: center;
            z-index: -2;
        }
        .fi-simple-page::after {
            content: '';
            position: fixed;
            top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            z-index: -1;
        }

        /* The glassmorphic card */
        .fi-simple-main {
            background: rgba(255, 255, 255, 0.05) !important;
            backdrop-filter: blur(16px) !important;
            -webkit-backdrop-filter: blur(16px) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 24px !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5) !important;
            padding: 2rem !important;
        }

        /* Headings and logo */
        .fi-simple-header-heading {
            font-size: 2.5rem !important;
            font-weight: 800 !important;
            background: -webkit-linear-gradient(#fcd34d, #f59e0b) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            margin-bottom: 1rem !important;
        }

        /* Make all text legible on dark */
        .fi-simple-main * {
            color: #f1f5f9;
        }
        .fi-simple-main p, .fi-simple-main span, .fi-simple-main label {
            color: #cbd5e1 !important;
        }

        /* Inputs */
        .fi-input {
            background: rgba(255, 255, 255, 0.1) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            color: white !important;
            border-radius: 12px !important;
        }
        .fi-input:focus {
            border-color: #f59e0b !important;
            box-shadow: 0 0 0 1px #f59e0b !important;
        }

        /* Buttons */
        .fi-btn-primary {
            background: linear-gradient(90deg, #f59e0b, #ea580c) !important;
            border: none !important;
            border-radius: 12px !important;
            color: white !important;
            font-weight: bold !important;
            transition: all 0.3s ease !important;
        }
        .fi-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(245, 158, 11, 0.4) !important;
        }
    </style>

    @if (filament()->hasRegistration())
        <x-slot name="subheading">
            {{ $this->registerAction }}
        </x-slot>
    @endif

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, scopes: $this->getRenderHookScopes()) }}

    <x-filament-panels::form id="form" wire:submit="authenticate">
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="$this->getCachedFormActions()"
            :full-width="$this->hasFullWidthFormActions()"
        />
    </x-filament-panels::form>

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, scopes: $this->getRenderHookScopes()) }}
</x-filament-panels::page.simple>

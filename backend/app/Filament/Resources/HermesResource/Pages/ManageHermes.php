<?php

namespace App\Filament\Resources\HermesResource\Pages;

use App\Services\HermesService;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\Page;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class ManageHermes extends Page
{
    use InteractsWithForms;
    protected static string $resource = \App\Filament\Resources\HermesResource::class;

    protected static ?string $title = 'مدیریت Hermes';

    /** @var array */
    public $data = [];

    /** @var string|null */
    public $chatResult = null;

    protected function getFormSchema(): array
    {
        $activeService = \App\Models\AiService::where('is_active', true)->first();
        
        return [
            Forms\Components\Select::make('provider')
                ->label('ارائه‌دهنده (Provider)')
                ->options([
                    'openai' => 'OpenAI',
                    'google' => 'Google (Gemini)',
                    'anthropic' => 'Anthropic',
                    'custom' => 'سفارشی',
                ])
                ->default($activeService?->provider ?? 'openai')
                ->required(),
            TextInput::make('model_name')
                ->label('نام مدل (Model)')
                ->default($activeService?->model_name ?? 'gpt-4o')
                ->required()
                ->placeholder('مثلاً gpt-4o یا gemini-1.5-pro'),
            TextInput::make('base_url')
                ->label('آدرس پایه (Base URL)')
                ->default($activeService?->base_url ?? 'https://api.openai.com/v1')
                ->required()
                ->placeholder('مثلاً https://api.openai.com/v1'),
            TextInput::make('api_key')
                ->label('کلید دسترسی API Key')
                ->placeholder($activeService?->api_key ? 'برای حفظ کلید فعلی خالی بگذارید' : 'کلید دسترسی را وارد کنید')
                ->password()
                ->revealable()
                ->dehydrated(fn ($state): bool => filled($state)),
            Textarea::make('chat')
                ->label('تست دستورات چت (محلی)')
                ->placeholder("مثلاً: فایل App.vue را پیدا کن...")
                ->rows(4),
        ];
    }

    public function submit()
    {
        $data = $this->form->getState();
        
        // Update or create the active AI Service in the database
        $activeService = \App\Models\AiService::where('is_active', true)->first();
        if (!$activeService) {
            $activeService = new \App\Models\AiService();
            $activeService->is_active = true;
            $activeService->name = 'Default Hermes Service';
        }
        
        if (isset($data['provider'])) $activeService->provider = $data['provider'];
        if (isset($data['model_name'])) $activeService->model_name = $data['model_name'];
        if (isset($data['base_url'])) $activeService->base_url = $data['base_url'];
        if (filled($data['api_key'] ?? null)) {
            $activeService->api_key = $data['api_key'];
        }
        
        $activeService->save();

        $chat = trim($data['chat'] ?? '');
        if ($chat === '') {
            $this->chatResult = 'دستوری وارد نشده.';
            return;
        }

        // تشخیص نوع دستور با regex ساده
        if (preg_match('/^search\s+(.+)$/i', $chat, $m)) {
            $pattern = trim($m[1]);
            $result = app(HermesService::class)->searchGraph($pattern);
            $this->chatResult = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } elseif (preg_match('/^trace\s+(\S+)\s+(inbound|outbound)$/i', $chat, $m)) {
            $func = $m[1];
            $direction = $m[2];
            $result = app(HermesService::class)->tracePath($func, $direction);
            $this->chatResult = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } elseif (preg_match('/^snippet\s+(.+)$/i', $chat, $m)) {
            $name = trim($m[1]);
            $result = app(HermesService::class)->getCodeSnippet($name);
            $this->chatResult = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else {
            // Use full Agent loop for natural language
            try {
                $agent = app(\App\Services\HermesAgent::class);
                
                // Retrieve existing session messages or initialize
                $messages = session()->get('hermes_chat_history', []);
                if (empty($messages)) {
                    $messages[] = [
                        'role' => 'system',
                        'content' => 'You are an autonomous AI coding assistant running inside a Laravel application. You have access to tools to read/write files and execute bash commands.'
                    ];
                }
                
                $messages[] = [
                    'role' => 'user',
                    'content' => $chat
                ];
                
                $messages = $agent->chat($messages);
                
                session()->put('hermes_chat_history', $messages);
                
                // Get the last assistant message
                $lastMsg = end($messages);
                if ($lastMsg && $lastMsg['role'] === 'assistant') {
                    $this->chatResult = $lastMsg['content'];
                } else {
                    $this->chatResult = 'No response or tool execution finished.';
                }
                
                // Keep the prompt empty for the next message
                $this->form->fill([
                    'provider' => $data['provider'],
                    'model_name' => $data['model_name'],
                    'base_url' => $data['base_url'],
                    'api_key' => $data['api_key'],
                    'chat' => '',
                ]);
            } catch (\Exception $e) {
                $this->chatResult = 'Error executing HermesAgent: ' . $e->getMessage();
            }
        }
    }

    public function getFormActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('save')
                ->label('ذخیره تنظیمات و اجرا')
                ->submit('submit'),
        ];
    }

}

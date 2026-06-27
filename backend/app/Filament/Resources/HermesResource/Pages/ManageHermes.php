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
        return [
            TextInput::make('endpoint')
                ->label('Endpoint هرمس')
                ->default(config('hermes.endpoint'))
                ->required()
                ->placeholder('مثلاً http://localhost:8000/api'),
            TextInput::make('api_key')
                ->label('API Key (اختیاری)')
                ->placeholder(config('hermes.api_key') ? 'برای حفظ کلید فعلی خالی بگذارید' : 'کلید دسترسی')
                ->password()
                ->revealable()
                ->dehydrated(fn ($state): bool => filled($state)),
            Textarea::make('chat')
                ->label('دستورات چت')
                ->placeholder("از دستورات زیر استفاده کنید:\nsearch <pattern>\ntrace <function> <inbound|outbound>\nsnippet <qualified_name>")
                ->rows(4),
        ];
    }

    public function submit()
    {
        $data = $this->form->getState();
        // Persist settings into .env. The API key is only overwritten when a new
        // value is supplied (empty input keeps the current key).
        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            $env = file_get_contents($envPath);
            $env = $this->setEnvValue($env, 'HERMES_ENDPOINT', $data['endpoint']);
            if (filled($data['api_key'] ?? null)) {
                $env = $this->setEnvValue($env, 'HERMES_API_KEY', $data['api_key']);
            }
            file_put_contents($envPath, $env);
            Artisan::call('config:clear');
            Artisan::call('config:cache');
        }

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
                    'endpoint' => $data['endpoint'],
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

    /**
     * Set or append an env key, quoting the value safely.
     */
    private function setEnvValue(string $env, string $key, string $value): string
    {
        $line = $key.'="'.addslashes($value).'"';

        if (preg_match('/^'.preg_quote($key, '/').'=.*/m', $env)) {
            return preg_replace('/^'.preg_quote($key, '/').'=.*/m', $line, $env);
        }

        return rtrim($env, "\n")."\n".$line."\n";
    }
}

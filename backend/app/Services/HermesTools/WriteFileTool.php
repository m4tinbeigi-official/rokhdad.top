<?php

namespace App\Services\HermesTools;

use Illuminate\Support\Facades\File;

class WriteFileTool implements ToolInterface
{
    public function getName(): string
    {
        return 'write_file';
    }

    public function getDescription(): string
    {
        return 'Write content to a file, overwriting it or creating a new one.';
    }

    public function getParameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'path' => [
                    'type' => 'string',
                    'description' => 'Absolute or relative path to the file'
                ],
                'content' => [
                    'type' => 'string',
                    'description' => 'The complete content to write to the file'
                ]
            ],
            'required' => ['path', 'content']
        ];
    }

    public function execute(array $arguments): string
    {
        $path = $arguments['path'] ?? '';
        $content = $arguments['content'] ?? '';
        if (!$path) {
            return "Error: Path is required.";
        }
        
        $fullPath = base_path($path);
        $directory = dirname($fullPath);
        
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }
        
        File::put($fullPath, $content);
        return "Successfully wrote to {$path}";
    }
}

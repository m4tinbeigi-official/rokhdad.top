<?php

namespace App\Services\HermesTools;

use Illuminate\Support\Facades\File;

class ReadFileTool implements ToolInterface
{
    public function getName(): string
    {
        return 'read_file';
    }

    public function getDescription(): string
    {
        return 'Read the contents of a file.';
    }

    public function getParameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'path' => [
                    'type' => 'string',
                    'description' => 'Absolute or relative path to the file'
                ]
            ],
            'required' => ['path']
        ];
    }

    public function execute(array $arguments): string
    {
        $path = $arguments['path'] ?? '';
        if (!$path) {
            return "Error: Path is required.";
        }
        $fullPath = base_path($path);
        if (!File::exists($fullPath)) {
            return "Error: File does not exist at {$path}";
        }
        return File::get($fullPath);
    }
}

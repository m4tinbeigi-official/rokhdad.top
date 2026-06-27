<?php

namespace App\Services\HermesTools;

use App\Services\HermesService;

class SearchGraphTool implements ToolInterface
{
    public function getName(): string
    {
        return 'search_graph';
    }

    public function getDescription(): string
    {
        return 'Search the codebase graph using regex pattern for functions, classes, etc.';
    }

    public function getParameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'pattern' => [
                    'type' => 'string',
                    'description' => 'Regex pattern to search'
                ]
            ],
            'required' => ['pattern']
        ];
    }

    public function execute(array $arguments): string
    {
        $pattern = $arguments['pattern'] ?? '';
        if (!$pattern) {
            return "Error: Pattern is required.";
        }
        
        $result = app(HermesService::class)->searchGraph($pattern);
        return json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}

<?php

namespace App\Services\HermesTools;

use Illuminate\Support\Facades\Process;

class RunCommandTool implements ToolInterface
{
    public function getName(): string
    {
        return 'run_command';
    }

    public function getDescription(): string
    {
        return 'Run a bash command on the server.';
    }

    public function getParameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'command' => [
                    'type' => 'string',
                    'description' => 'The shell command to execute'
                ]
            ],
            'required' => ['command']
        ];
    }

    public function execute(array $arguments): string
    {
        $command = $arguments['command'] ?? '';
        if (!$command) {
            return "Error: Command is required.";
        }
        
        // DANGEROUS: For a real production app, restrict or disable this!
        // We allow it here per user's "full automation" request.
        $result = Process::path(base_path())->run($command);
        
        $output = $result->output();
        $errorOutput = $result->errorOutput();
        
        if ($result->successful()) {
            return $output ?: "Command executed successfully with no output.";
        }
        
        return "Command failed. Output:\n{$output}\nError Output:\n{$errorOutput}";
    }
}

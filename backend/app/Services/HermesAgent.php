<?php

namespace App\Services;

use App\Services\HermesTools\ReadFileTool;
use App\Services\HermesTools\RunCommandTool;
use App\Services\HermesTools\SearchGraphTool;
use App\Services\HermesTools\WriteFileTool;
use Illuminate\Support\Facades\Log;

class HermesAgent
{
    protected AiManager $aiManager;
    protected array $tools = [];

    public function __construct(AiManager $aiManager)
    {
        $this->aiManager = $aiManager;
        
        $this->registerTool(new ReadFileTool());
        $this->registerTool(new WriteFileTool());
        $this->registerTool(new RunCommandTool());
        $this->registerTool(new SearchGraphTool());
    }

    public function registerTool($tool)
    {
        $this->tools[$tool->getName()] = $tool;
    }

    protected function getToolsArray(): array
    {
        $toolsFormat = [];
        foreach ($this->tools as $tool) {
            $toolsFormat[] = [
                'type' => 'function',
                'function' => [
                    'name' => $tool->getName(),
                    'description' => $tool->getDescription(),
                    'parameters' => $tool->getParameters(),
                ]
            ];
        }
        return $toolsFormat;
    }

    /**
     * Run the agent loop.
     * Takes an array of messages and returns the updated array of messages including agent responses and tool outputs.
     */
    public function chat(array $messages, int $maxSteps = 5): array
    {
        $step = 0;
        
        while ($step < $maxSteps) {
            try {
                $response = $this->aiManager->chatCompletions($messages, [
                    'tools' => $this->getToolsArray(),
                    'tool_choice' => 'auto',
                ]);
                
                $message = $response['choices'][0]['message'] ?? null;
                
                if (!$message) {
                    break;
                }
                
                $messages[] = $message;

                // If AI wants to call tools
                if (!empty($message['tool_calls'])) {
                    foreach ($message['tool_calls'] as $toolCall) {
                        $toolName = $toolCall['function']['name'];
                        $arguments = json_decode($toolCall['function']['arguments'], true) ?? [];
                        
                        $toolResult = "Tool not found.";
                        if (isset($this->tools[$toolName])) {
                            try {
                                $toolResult = $this->tools[$toolName]->execute($arguments);
                            } catch (\Throwable $e) {
                                $toolResult = "Error executing tool: " . $e->getMessage();
                            }
                        }
                        
                        // Prevent output from being too massive
                        if (strlen($toolResult) > 50000) {
                            $toolResult = substr($toolResult, 0, 50000) . "\n... [TRUNCATED]";
                        }

                        $messages[] = [
                            'role' => 'tool',
                            'tool_call_id' => $toolCall['id'],
                            'content' => $toolResult
                        ];
                    }
                } else {
                    // AI responded with a final message
                    break;
                }
                
            } catch (\Exception $e) {
                Log::error('HermesAgent error: ' . $e->getMessage());
                $messages[] = [
                    'role' => 'assistant',
                    'content' => 'Error: ' . $e->getMessage()
                ];
                break;
            }
            
            $step++;
        }
        
        return $messages;
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\HermesAgent;
use Illuminate\Http\Request;

class HermesAgentController extends Controller
{
    public function chat(Request $request, HermesAgent $agent)
    {
        $request->validate([
            'message' => 'required|string',
        ]);
        
        $chat = $request->input('message');

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
        
        // Return the last message
        $lastMsg = end($messages);
        return response()->json([
            'response' => $lastMsg['content'] ?? 'No response',
            'messages' => $messages
        ]);
    }
    
    public function clearSession()
    {
        session()->forget('hermes_chat_history');
        return response()->json(['status' => 'cleared']);
    }
}

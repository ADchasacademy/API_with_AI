<?php

namespace App\Http\Controllers;

use App\Models\ChatHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
    public function chat(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string',
            'session_id' => 'nullable|string',
        ]);

        $user = Auth::user();
        $sessionId = $validated['session_id'] ?? (string) Str::uuid();

        $previousMessages = [];
        if ($sessionId) {
            $previousMessages = ChatHistory::where('session_id', $sessionId)
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn ($chat) => [
                    ['role' => 'user', 'content' => $chat->user_message],
                    ['role' => 'assistant', 'content' => $chat->bot_response],
                ])
                ->flatten(1)
                ->toArray();
        }

        $messages = array_merge($previousMessages, [
            ['role' => 'user', 'content' => $validated['message']],
        ]);

        $prompt = collect($messages)->map(fn ($message) => "{$message['role']}: {$message['content']}")->implode("\n");

        $response = Http::post('http://localhost:11434/api/generate', [
            'model' => 'llama3.2:1b',
            'prompt' => $prompt,
            'stream' => false,
        ]);

        if ($response->successful()) {
            $responseData = $response->json();

            Log::info('LLM Response Data: ', $responseData);

            if (isset($responseData['response'])) {
                ChatHistory::create([
                    'user_id' => $user->id,
                    'session_id' => $sessionId,
                    'user_message' => $validated['message'],
                    'bot_response' => $responseData['response'],
                ]);

                return response()->json([
                    'message' => $responseData['response'],
                    'session_id' => $sessionId,
                ]);
            } else {
                return response()->json([
                    'error' => 'Response key not found in LLM response',
                ], 500);
            }
        } else {
            return response()->json([
                'error' => 'Failed to communicate with LLM',
            ], 500);
        }
    }
}

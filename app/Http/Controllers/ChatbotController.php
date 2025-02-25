<?php

namespace App\Http\Controllers;

use App\Models\ChatHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
    public function chat(Request $request)
    {
        $userInput = $request->input('message');
        $sessionId = $request->input('session_id');
        $user = $request->user();

        // Save the chat history with cookies for guests
        if (! $sessionId) {
            $sessionId = $request->cookie('session_id');
        }

        // If no session_id found, create a new one for guests or new sessions
        if (! $sessionId) {
            $sessionId = (string) Str::uuid();
        }

        // check if there's an active session and is linked to the user
        $activeSession = ChatHistory::where('session_id', $sessionId)
            ->where('user_id', $user ? $user->id : null)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($activeSession) {
            // Fetch previous messages for the session
            $previousMessages = ChatHistory::where('session_id', $sessionId)
                ->where(function ($query) use ($user) {
                    if ($user) {
                        $query->where('user_id', $user->id);
                    }
                })
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(fn ($chat) => [
                    ['role' => 'user', 'content' => $chat->user_message],
                    ['role' => 'assistant', 'content' => $chat->bot_response],
                ])
                ->flatten(1)
                ->toArray();

            $messages = array_merge($previousMessages, [
                ['role' => 'user', 'content' => $userInput],
            ]);
        } else {
            // No active session, create a new one
            $sessionId = (string) Str::uuid();
            $messages = [
                ['role' => 'user', 'content' => $userInput],
            ];
        }

        // Send the messages to the Ollama API
        try {
            $response = Http::post('http://localhost:11434/api/chat', [
                'model' => 'llama3.2:1b',
                'messages' => $messages,
                'stream' => false,
            ]);

            if ($response->failed()) {
                throw new \Exception('Ollama API request failed');
            }

            $botResponse = $response->json()['message']['content'];
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Ollama API request failed: '.$e->getMessage().' Response Code: '.$response->status());

            return response()->json(['error' => 'There was a problem connecting to the service. Please try again later.'], 500);
        }
        // Save the chat history in the database
        if ($user) {
            ChatHistory::create([
                'user_id' => $user->id,
                'session_id' => $sessionId,
                'user_message' => $userInput,
                'bot_response' => $botResponse,
            ]);
        }
        // Save the session_id in a cookie for guests up to 24 hours
        $cookie = cookie('session_id', $sessionId, 1440);

        return response()->json([
            'response' => $botResponse,
            'session_id' => $user ? $sessionId : null,
        ])->cookie($cookie);
    }

    // Show chat history for authenticated users
    public function showChatHistory(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'You must be logged in!'], 401);
        }

        $history = ChatHistory::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['history' => $history]);
    }

    // show chat session history for only authenticated users
    public function showChatSessionHistory(Request $request, $sessionId)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'You must be logged in!'], 401);
        }

        $history = ChatHistory::where('user_id', $user->id)
            ->where('session_id', $sessionId)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json(['history' => $history]);
    }

    // Delete session chat history for authenticated users
    public function deleteChatHistory(Request $request, $sessionId)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'You must be logged in!'], 401);
        }

        $deleted = ChatHistory::where('session_id', $sessionId)
            ->where('user_id', $user->id)
            ->delete();

        if ($deleted) {
            return response()->json(['message' => 'Chat history deleted']);
        }

        return response()->json(['message' => 'You are not authorized to delete this chat history'], 403);
    }
}

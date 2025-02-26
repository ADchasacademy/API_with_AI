<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatbotController;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/chatbot/chat', [ChatbotController::class, 'chat'])->name('chat');
    Route::get('/chat/history', [ChatbotController::class, 'showChatHistory'])->name('chat.history');
    Route::get('/chat/history/{sessionId}', [ChatbotController::class, 'showChatSessionHistory'])->name('chat.session.history');
    Route::delete('/chat/history/{sessionId}', [ChatbotController::class, 'deleteChatHistory'])->name('chat.history.delete');
});

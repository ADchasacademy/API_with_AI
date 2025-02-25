<?php

use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::post('/chat', [ChatbotController::class, 'chat'])->name('chat');
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/chat/history', [ChatbotController::class, 'showChatHistory'])->name('chat.history');
    Route::get('/chat/history/{sessionId}', [ChatbotController::class, 'showChatSessionHistory'])->name('chat.session.history');
    Route::delete('/chat/history/{sessionId}', [ChatbotController::class, 'deleteChatHistory'])->name('chat.history.delete');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';

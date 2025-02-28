<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ChatbotController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthenticatedSessionController::class, 'register']);
Route::post('/login', [AuthenticatedSessionController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/chat', [ChatbotController::class, 'chat'])->name('chat');
    Route::get('/logout', [AuthenticatedSessionController::class, 'logout'])->name('logout');
});

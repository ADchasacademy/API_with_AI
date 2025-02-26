<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;
use App\Models\User;

// Route::get('/', function () {
//     $user = User::find(1);
//     if (!$user) {
//         return 'User not found!';
//     }

//     $token = $user->createToken('asd')->plainTextToken;
//     dd($token);
// });




require __DIR__ . '/auth.php';

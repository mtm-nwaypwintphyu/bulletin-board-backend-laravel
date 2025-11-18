<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/login', [AuthController::class, 'login'])->name('login');

// create user account (public)
Route::post('/create-account', [AuthController::class, 'createAccount']);

Route::middleware('auth:sanctum')->group(function () {
    // create user by admin
    Route::post('/create-user', [App\Http\Controllers\Admin\UserController::class, 'createUser']);

    // logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // get profile
    Route::get('/user/profile', [AuthController::class, 'profile']); 
});

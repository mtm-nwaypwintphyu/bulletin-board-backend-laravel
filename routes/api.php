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

// forgot password
Route::post('/password/forgot', [App\Http\Controllers\PasswordController::class, 'forgot']);

// password reset
Route::post('/password/reset', [App\Http\Controllers\PasswordController::class, 'reset']);

Route::middleware('auth:sanctum')->group(function () {
    // create user by admin
    Route::post('/create-user', [App\Http\Controllers\Admin\UserController::class, 'createUser']);

    // logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // get profile
    Route::get('/user/profile', [AuthController::class, 'profile']);

    // update user
    Route::put('/user/update', [App\Http\Controllers\Admin\UserController::class, 'update']);

    // get all users
    Route::get('/users',[App\Http\Controllers\Admin\UserController::class, 'index']);

    // delete user
    Route::delete('/users/{id}',[App\Http\Controllers\Admin\UserController::class, 'destroy']);

    // upload user csv
    Route::post('/users/import',[App\Http\Controllers\Admin\UserController::class, 'import']);

    // change password
    Route::post('/password/change',[App\Http\Controllers\PasswordController::class, 'change']);

});

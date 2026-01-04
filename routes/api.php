<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\RegistrationController;
use App\Http\Controllers\Api\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Without authentication
Route::get('/ping', function () {return response()->json(['message' => 'API működik!']);});
Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);
Route::post('/register', [\App\Http\Controllers\Api\AuthController::class, 'register']);

// ---------------------------
// Authenticated routes (Sanctum)
// ---------------------------
Route::middleware('auth:sanctum')->group(function () {

    // ---------------------------
    // Event CRUD + regisztráció
    // ---------------------------
    Route::prefix('events')->group(function () {
        Route::get('/', [EventController::class, 'index']);
        Route::get('/upcoming', [EventController::class, 'upcoming']);
        Route::get('/past', [EventController::class, 'past']);
        Route::get('/filter', [EventController::class, 'filter']);

        // Admin only CRUD
        Route::post('/', [EventController::class, 'store']);
        Route::put('/{id}', [EventController::class, 'update']);
        Route::delete('/{id}', [EventController::class, 'destroy']);

        // Registrations
        Route::post('{event}/register', [RegistrationController::class, 'register']);    // user regisztrál
        Route::delete('{event}/unregister', [RegistrationController::class, 'unregister']); // user törli magát
        Route::delete('{event}/users/{user}', [RegistrationController::class, 'adminRemoveUser']); // admin törli usert
    });

    // ---------------------------
    // User CRUD (admin only)
    // ---------------------------
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::post('/', [UserController::class, 'store']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
    });

    Route::get('/me', [UserController::class, 'me']);
    Route::put('/me', [UserController::class, 'updateMe']);
    Route::post('/logout', [UserController::class, 'logout']);
});


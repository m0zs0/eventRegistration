<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\RegistrationController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Without authentication
Route::get('/ping', function () {return response()->json(['message' => 'API működik!']);});
Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);
Route::post('/register', [\App\Http\Controllers\Api\AuthController::class, 'register']);

// Teszt email küldése
Route::get('/test-mail', function () {
    Mail::raw('Ez egy teszt email Laravelből', function ($message) {
        $message->to('teszt@mailtrap.io')
                ->subject('Laravel Mailtrap teszt');
    });

    return 'Mail sent';
});

// Email megerősítő link kezelése
Route::get('/email/verify/{id}/{hash}', function ($id, $hash) {

    // Felhasználó lekérése
    $user = User::findOrFail($id);

    // Ellenőrizzük, hogy a hash megegyezik az email hash-sel
    if (! hash_equals((string) $hash, sha1($user->email))) {
        abort(403, 'Invalid verification link');
    }

    // Már megerősített felhasználó
    if ($user->hasVerifiedEmail()) {
        return response()->json(['message' => 'Email already verified']);
    }

    // Email megerősítése
    $user->markEmailAsVerified();

    return response()->json(['message' => 'Email successfully verified']);

})->name('verification.verify')
  ->middleware('signed'); // aláírt URL ellenőrzés

// Megerősítő email újraküldése
Route::post('/email/verification-notification', function (Request $request) {
    $request->validate([
        'email' => 'required|email'
    ]);

    $user = User::where('email', $request->email)->firstOrFail();

    if ($user->hasVerifiedEmail()) {
        return response()->json([
            'message' => 'Email already verified'
        ], 400);
    }

    // Email küldése
    $user->sendEmailVerificationNotification();

    return response()->json([
        'message' => 'Verification email sent'
    ]);
})
->middleware('throttle:3,1'); // max 3 kérelem / perc


// ---------------------------
// Authenticated routes (Sanctum)
// ---------------------------
Route::middleware('auth:sanctum', 'verified')->group(function () {

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


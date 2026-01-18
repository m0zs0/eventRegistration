<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Events\Registered;

class AuthController extends Controller
{
    /**
     * Regisztráció és token létrehozása
     */
    public function register(Request $request)
    {
        try {

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|confirmed|min:6',
                'phone' => 'nullable|string|max:20',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Failed to register user',
                'errors' => $e->errors() // visszaadja, mely mezők hibásak
            ], 422);
        }
    

        $validated['password'] = Hash::make($validated['password']);
        $validated['remember_token'] = Str::random(10);

        $user = User::create($validated);

        //megerősítő email küldése
        event(new Registered($user));

        return response()->json([
            'message' => 'User created successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ],
        ], 201);

    }

    /**
     * Bejelentkezés és token kiadása
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        // Hibás email vagy jelszó
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid email or password'], 401);
        }

        // Email NINCS megerősítve
        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email address is not verified'
            ], 403);
        }

        // Token létrehozása
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'access' => [
                'token' => $token,
                'token_type' => 'Bearer'
            ]
        ]);

    }

    /**
     * Kijelentkezés: aktuális token törlése
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $user->currentAccessToken()->delete();
        }

        return response()->json(['message' => 'Logout successful']);
    }
}

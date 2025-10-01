<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected $user;

    public function __construct()
    {
        $this->user = auth()->user();
    }


    public function login(Request $request)
    {
        $credentials = $request->only('username', 'password');

        if (auth()->attempt($credentials)) {
            $user = auth()->user();
            $token = $user->createToken('auth_token')->plainTextToken;

            $cookie = cookie('sanctum', $token, 3600);

            return response()->json([
                'message' => 'Login successful',
                'access_token' => $token,
                'user' => $user
            ])->withCookie($cookie);
        }

        return response()->json(['message' => 'Invalid credentials'], 401);

    }

    public function logout()
    {
        $user = auth()->user();
        $user->tokens()->delete();
        $cookie = cookie()->forget('sanctum');

        return response()->json(['message' => 'Logged out successfully'])->withCookie($cookie);
    }
}

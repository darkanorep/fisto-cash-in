<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AuthController extends Controller
{

    public function login(Request $request)
    {
        $credentials = $request->only('username', 'password');

        if (auth()->attempt($credentials)) {
            $user = auth()->user();
            $token = $user->createToken('auth_token')->plainTextToken;

            $cookie = cookie('sanctum', $token);

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

    public function resetPassword($id) {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->password = bcrypt($user->username);
        $user->save();

        return response()->json(['message' => 'Password reset successfully']);
    }
}

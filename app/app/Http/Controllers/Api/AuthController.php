<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['nullable', 'string'],
            'login' => ['nullable', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $login = $validated['login'] ?? $validated['email'] ?? null;
        if (!$login) {
            throw ValidationException::withMessages([
                'login' => ['Il campo login o email è obbligatorio.'],
            ]);
        }

        $user = User::query()
            ->where('email', $login)
            ->orWhere('name', $login)
            ->first();

        if (!$user || !Hash::check($validated['password'], (string) $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['Credenziali non valide.'],
            ]);
        }

        $token = $user
            ->createToken($validated['device_name'] ?? 'api-client')
            ->plainTextToken;

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $token,
            'token' => $token,
            'bearer_token' => $token,
            'user' => [
                'id' => (string) $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => null,
                'roles' => [],
                'permissions' => [],
            ],
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => (string) $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => null,
            ],
            'roles' => [],
            'permissions' => [],
        ]);
    }

    public function logout(Request $request)
    {
        $token = $request->user()?->currentAccessToken();
        if ($token) {
            $token->delete();
        }

        return response()->json([
            'message' => 'Logout effettuato',
        ]);
    }
}

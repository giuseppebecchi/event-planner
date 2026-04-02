<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => ['nullable', 'string'],
            'login' => ['nullable', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $login = $validated['login'] ?? $validated['email'] ?? null;
        if (!$login) {
            throw ValidationException::withMessages([
                'login' => 'Il campo login o email è obbligatorio.',
            ]);
        }

        $user = User::query()
            ->where('email', $login)
            ->orWhere('name', $login)
            ->first();

        if (!$user || !Hash::check($validated['password'], (string) $user->password)) {
            throw ValidationException::withMessages([
                'login' => 'Credenziali non valide.',
            ]);
        }

        Auth::guard('web')->login($user, (bool) ($validated['remember'] ?? false));
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Login effettuato',
            'user' => [
                'id' => (string) $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => [],
                'permissions' => [],
            ],
        ]);
    }

    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}

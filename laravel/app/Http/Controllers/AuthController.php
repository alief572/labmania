<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $input = $request->validate(['username' => 'required|string|max:45', 'password' => 'required|string|max:4096']);
        $matches = User::where('username', $input['username'])->limit(2)->get();
        $user = $matches->count() === 1 ? $matches->first() : null;
        $valid = password_verify($input['password'], $user?->password ?? '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.');
        if (! $valid || ! $user?->password || ! $user->active()) {
            $request->session()->forget(['auth.pending_id', 'auth.pending_until', 'auth.setup_secret']);
            Log::warning('auth.login_failed', ['ip' => $request->ip()]);
            throw ValidationException::withMessages(['username' => 'Username atau password tidak valid.']);
        }

        $request->session()->regenerate();
        $request->session()->forget(['auth.setup_secret', 'auth.verified_id', 'auth.factor', 'auth.mode', 'auth.pending_id', 'auth.pending_until']);
        $user = DB::transaction(function () use ($request, $user) {
            $current = User::lockForUpdate()->findOrFail($user->id_user);
            abort_unless($current->active() && $current->password === $user->password, 403);
            $current->login_terakhir = now();
            $current->ip = $request->ip();
            $current->save();

            return $current;
        });
        Auth::login($user);
        $request->session()->put(['auth.verified_id' => $user->id_user, 'auth.mode' => 'password', 'auth.factor' => hash('sha256', $user->password)]);
        $request->session()->regenerate();
        Inertia::clearHistory();

        return redirect()->intended('/');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Inertia::clearHistory();

        return to_route('login');
    }
}

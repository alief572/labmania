<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\LocalUat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

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
        if (LocalUat::passwordOnly()) {
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
        $request->session()->put(['auth.pending_id' => $user->id_user, 'auth.pending_until' => now()->addMinutes(5)->timestamp]);

        return to_route('two-factor');
    }

    private function pending(Request $request): User
    {
        $user = User::find($request->session()->get('auth.pending_id'));
        if (! $user?->active() || $request->session()->get('auth.pending_until', 0) < time()) {
            $request->session()->invalidate();
            abort(redirect()->route('login'));
        }

        return $user;
    }

    public function challenge(Request $request, TwoFactorAuthenticationProvider $totp)
    {
        $user = $this->pending($request);
        $secret = null;
        $needsToken = ! $user->ga_secret;
        if ($needsToken && Cache::has('auth.enroll.'.$user->id_user)) {
            $secret = $request->session()->get('auth.setup_secret');
            if (! $secret) {
                $request->session()->put('auth.setup_secret', $secret = $totp->generateSecretKey());
            }
        }

        return Inertia::render('Auth/TwoFactor', [
            'needsToken' => $needsToken,
            'enrollBlocked' => $needsToken && ! $secret,
            'secret' => $secret,
            'setupUrl' => $secret ? $totp->qrCodeUrl(config('app.name'), $user->username, $secret) : null,
        ])->toResponse($request)->header('Cache-Control', 'no-store, private');
    }

    public function verify(Request $request, TwoFactorAuthenticationProvider $totp)
    {
        $request->validate(['code' => 'required|digits:6', 'enroll_token' => 'nullable|string|max:40']);
        $user = $this->pending($request);
        $enrolling = ! $user->ga_secret;
        if ($enrolling) {
            $stored = Cache::get('auth.enroll.'.$user->id_user);
            $given = strtoupper(trim((string) $request->input('enroll_token')));
            if (! $stored || $given === '' || ! hash_equals($stored, hash('sha256', $given))) {
                Log::warning('auth.enroll_token_invalid', ['ip' => $request->ip()]);
                throw ValidationException::withMessages(['enroll_token' => 'Token enroll tidak valid atau kedaluwarsa. Hubungi admin.']);
            }
        }
        $secret = $user->totpSecret() ?? $request->session()->get('auth.setup_secret');
        $code = $request->string('code')->toString();
        $valid = $secret && Cache::lock('auth.totp.'.hash('sha256', $code), 10)
            ->get(fn () => $totp->verify($secret, $code));
        if (! $valid) {
            Log::warning('auth.two_factor_failed', ['ip' => $request->ip()]);
            throw ValidationException::withMessages(['code' => 'Kode autentikator tidak valid atau sudah digunakan.']);
        }

        try {
            $user = DB::transaction(function () use ($request, $user, $secret) {
                $current = User::lockForUpdate()->findOrFail($user->id_user);
                abort_unless($current->active() && $current->ga_secret === $user->ga_secret, 403);
                $old = $current->only(['ga_secret']);
                $current->login_terakhir = now();
                $current->ip = $request->ip();
                if (! $current->ga_secret) {
                    $current->ga_secret = 'encrypted:'.Crypt::encryptString($secret);
                }
                $current->save();
                Auth::setUser($current);
                if (! $old['ga_secret']) {
                    write_log('Master Data', 'Edit', 'Aktivasi autentikator', ['old' => $old, 'new' => $current->only(['ga_secret'])], 'users', $current->id_user, $current->username);
                }

                return $current;
            });
        } finally {
            Auth::forgetUser();
        }
        if ($enrolling) {
            Cache::forget('auth.enroll.'.$user->id_user);
        }
        Auth::login($user);
        $request->session()->put(['auth.verified_id' => $user->id_user, 'auth.factor' => hash('sha256', $user->ga_secret)]);
        $request->session()->forget(['auth.pending_id', 'auth.pending_until', 'auth.setup_secret']);
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

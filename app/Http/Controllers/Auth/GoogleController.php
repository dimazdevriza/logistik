<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Events\TwoFactorAuthenticationChallenged;
use Laravel\Fortify\Fortify;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleController extends Controller
{
    /**
     * Redirect user to Google OAuth page.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    /**
     * Handle Google OAuth callback.
     * STRICT WHITELIST: Only log in if email exists in database. Do NOT create new accounts.
     */
    public function callback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('login')->withErrors([
                'email' => 'Gagal terhubung dengan Google. Silakan coba lagi.',
            ]);
        }

        $email = strtolower(trim((string) $googleUser->getEmail()));
        $googleId = (string) $googleUser->getId();
        $emailVerified = (bool) ($googleUser->getRaw()['email_verified'] ?? false);

        if ($email === '' || $googleId === '' || ! $emailVerified) {
            return redirect()->route('login')->withErrors([
                'email' => 'Google tidak dapat memverifikasi alamat email akun ini.',
            ]);
        }

        // Check if user email is pre-registered by Admin
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user) {
            return redirect()->route('login')->withErrors([
                'email' => "Akun Google ({$email}) tidak terdaftar dalam sistem. Hubungi Administrator untuk mendapatkan akses.",
            ]);
        }

        if ($user->google_id && ! hash_equals($user->google_id, $googleId)) {
            return redirect()->route('login')->withErrors([
                'email' => 'Akun ini sudah terhubung dengan akun Google lain. Hubungi Administrator.',
            ]);
        }

        if (User::where('google_id', $googleId)->whereKeyNot($user->id)->exists()) {
            return redirect()->route('login')->withErrors([
                'email' => 'Akun Google ini sudah terhubung dengan pengguna lain.',
            ]);
        }

        if (! $user->google_id) {
            $user->forceFill([
                'google_id' => $googleId,
                'google_linked_at' => now(),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
        }

        if ($user->two_factor_secret && (! Fortify::confirmsTwoFactorAuthentication() || $user->two_factor_confirmed_at)) {
            $request->session()->put([
                'login.id' => $user->getKey(),
                'login.remember' => true,
            ]);

            TwoFactorAuthenticationChallenged::dispatch($user);

            return redirect()->route('two-factor.login');
        }

        // Log in pre-authorized user
        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}

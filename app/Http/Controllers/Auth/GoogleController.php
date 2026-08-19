<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleController extends Controller
{
    /**
     * Redirect user to Google OAuth page.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google OAuth callback.
     * STRICT WHITELIST: Only log in if email exists in database. Do NOT create new accounts.
     */
    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            return redirect()->route('login')->withErrors([
                'email' => 'Gagal terhubung dengan layanan Google: '.$e->getMessage(),
            ]);
        }

        $email = strtolower(trim($googleUser->getEmail()));

        // Check if user email is pre-registered by Admin
        $user = User::where('email', $email)->first();

        if (! $user) {
            return redirect()->route('login')->withErrors([
                'email' => "Akun Google ({$email}) tidak terdaftar dalam sistem. Hubungi Administrator untuk mendapatkan akses.",
            ]);
        }

        // Log in pre-authorized user
        Auth::login($user, true);

        return redirect()->intended('/dashboard');
    }
}

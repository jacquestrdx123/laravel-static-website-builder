<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable) {
            return redirect()
                ->route('login')
                ->with('error', 'Google sign-in failed. Please try again.');
        }

        $googleId = (string) $googleUser->getId();
        $email = $googleUser->getEmail();

        if ($email === null || $email === '') {
            return redirect()
                ->route('login')
                ->with('error', 'Google did not provide an email address.');
        }

        $user = User::query()->where('google_id', $googleId)->first();

        if ($user === null) {
            $user = User::query()->where('email', $email)->first();

            if ($user !== null) {
                $user->forceFill([
                    'google_id' => $googleId,
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ])->save();
            } else {
                $user = User::create([
                    'name' => $googleUser->getName() ?: strstr($email, '@', true) ?: $email,
                    'email' => $email,
                    'google_id' => $googleId,
                    'password' => null,
                ]);

                $user->forceFill(['email_verified_at' => now()])->save();
            }
        }

        Auth::login($user);
        request()->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}

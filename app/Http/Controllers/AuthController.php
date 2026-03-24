<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    /**
     * Login with nickname (no password required).
     */
    public function loginNickname(Request $request)
    {
        $validated = $request->validate([
            'nickname' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        try {
            $user = User::firstOrCreate(
                ['nickname' => $validated['nickname']],
                [
                    'name' => $validated['nickname'],
                    'email' => $validated['email'] ?? null,
                    'provider' => 'nickname',
                ]
            );

            Auth::login($user, remember: true);

            return redirect()->intended('/');
        } catch (\Exception $e) {
            Log::error('Nickname login failed', ['error' => $e->getMessage()]);

            return back()->withErrors([
                'nickname' => 'ไม่สามารถเข้าสู่ระบบได้ กรุณาลองใหม่',
            ]);
        }
    }

    /**
     * Redirect to Google OAuth.
     */
    public function redirectGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google OAuth callback.
     */
    public function callbackGoogle()
    {
        try {
            // Try stateful first; fall back to stateless if session/state was lost
            // (common with SameSite cookie policies and cross-origin redirects)
            try {
                $socialUser = Socialite::driver('google')->user();
            } catch (\Laravel\Socialite\Two\InvalidStateException $e) {
                Log::warning('Google OAuth state mismatch, retrying stateless', [
                    'error' => $e->getMessage(),
                ]);
                $socialUser = Socialite::driver('google')->stateless()->user();
            }

            $user = User::updateOrCreate(
                [
                    'provider' => 'google',
                    'provider_id' => $socialUser->getId(),
                ],
                [
                    'name' => $socialUser->getName(),
                    'nickname' => $socialUser->getNickname() ?? $socialUser->getName(),
                    'email' => $socialUser->getEmail(),
                    'avatar_url' => $socialUser->getAvatar(),
                ]
            );

            Auth::login($user, remember: true);

            return redirect()->intended('/');
        } catch (\Exception $e) {
            Log::error('Google login failed', ['error' => $e->getMessage()]);

            return redirect('/login')->withErrors([
                'social' => 'ไม่สามารถเข้าสู่ระบบด้วย Google ได้',
            ]);
        }
    }

    /**
     * Redirect to LINE OAuth.
     */
    public function redirectLine()
    {
        return Socialite::driver('line')->redirect();
    }

    /**
     * Handle LINE OAuth callback.
     */
    public function callbackLine()
    {
        try {
            try {
                $socialUser = Socialite::driver('line')->user();
            } catch (\Laravel\Socialite\Two\InvalidStateException $e) {
                Log::warning('LINE OAuth state mismatch, retrying stateless', [
                    'error' => $e->getMessage(),
                ]);
                $socialUser = Socialite::driver('line')->stateless()->user();
            }

            $user = User::updateOrCreate(
                [
                    'provider' => 'line',
                    'provider_id' => $socialUser->getId(),
                ],
                [
                    'name' => $socialUser->getName(),
                    'nickname' => $socialUser->getNickname() ?? $socialUser->getName(),
                    'email' => $socialUser->getEmail(),
                    'avatar_url' => $socialUser->getAvatar(),
                ]
            );

            Auth::login($user, remember: true);

            return redirect()->intended('/');
        } catch (\Exception $e) {
            Log::error('LINE login failed', ['error' => $e->getMessage()]);

            return redirect('/login')->withErrors([
                'social' => 'ไม่สามารถเข้าสู่ระบบด้วย LINE ได้',
            ]);
        }
    }

    /**
     * Logout the current user.
     */
    public function logout()
    {
        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/');
    }
}

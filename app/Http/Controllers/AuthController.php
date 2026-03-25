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
     * Only creates NEW nickname accounts. Cannot impersonate existing
     * OAuth (Google/LINE) users or admin accounts.
     */
    public function loginNickname(Request $request)
    {
        $validated = $request->validate([
            'nickname' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        try {
            $nickname = $validated['nickname'];

            // Check if nickname is taken by an OAuth or admin user — prevent impersonation
            $existing = User::where('nickname', $nickname)->first();
            if ($existing) {
                // Allow re-login only if it's a nickname-provider account (not OAuth/admin)
                if ($existing->provider !== 'nickname') {
                    return back()->withErrors([
                        'nickname' => 'ชื่อนี้ถูกใช้โดยบัญชี Google/LINE แล้ว กรุณาใช้ชื่ออื่น',
                    ]);
                }
                if ($existing->is_admin) {
                    return back()->withErrors([
                        'nickname' => 'ไม่สามารถเข้าสู่ระบบด้วยชื่อนี้ได้',
                    ]);
                }
                // Same nickname-provider user — allow re-login
                Auth::login($existing, remember: true);
                return redirect()->intended('/');
            }

            // Create new nickname account
            $user = User::create([
                'nickname' => $nickname,
                'name' => $nickname,
                'email' => $validated['email'] ?? null,
                'provider' => 'nickname',
            ]);

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

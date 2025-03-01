<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;

class SocialAuthController extends Controller
{
    public function redirectToProvider($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    public function handleProviderCallback($provider)
    {
        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication failed.',
                'error' => $e->getMessage(),
            ], 400);
        }

        // Cek apakah email sudah terdaftar secara manual
        $existingUser = User::where('email', $socialUser->getEmail())->first();

        if ($existingUser && !$existingUser->provider) {
            return response()->json([
                'success' => false,
                'message' => 'This email is already registered manually. Please log in using your password.',
            ], 403);
        }

        // Jika user sudah ada dengan Google, update datanya
        $user = User::updateOrCreate(
            [
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
            ],
            [
                'name' => $socialUser->getName(),
                'email' => $socialUser->getEmail(),
                'avatar' => $socialUser->getAvatar(),
            ]
        );

        Auth::login($user);
        $user->update(['last_logged_in_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Authenticated successfully.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'provider' => $user->provider,
                'avatar' => $user->avatar,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ],
            'token' => $user->createToken('API Token')->accessToken,
        ]);
    }
}

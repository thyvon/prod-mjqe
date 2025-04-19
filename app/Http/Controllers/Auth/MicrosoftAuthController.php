<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str; // For generating random passwords


class MicrosoftAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('microsoft')
            ->scopes(['Sites.ReadWrite.All', 'Files.ReadWrite', 'offline_access']) // Add required scopes
            ->with(['prompt' => 'login']) // Force Microsoft to show the login page
            ->redirect();
    }

    public function callback()
    {
        try {
            // Retrieve the Microsoft user
            $microsoftUser = Socialite::driver('microsoft')->user();

            // Find or create user
            $user = User::updateOrCreate(
                ['email' => $microsoftUser->getEmail()],
                [
                    'name'                    => $microsoftUser->getName(),
                    'microsoft_id'            => $microsoftUser->getId(),
                    'microsoft_token'         => $microsoftUser->token,
                    'microsoft_refresh_token' => $microsoftUser->refreshToken,
                    'password'                => bcrypt(Str::random(16)),
                ]
            );

            // Log the user in
            Auth::login($user);

            return redirect('/'); // Redirect to the dashboard or desired route
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Microsoft OAuth Callback Error: ' . $e->getMessage());
            Log::info('Microsoft callback request:', request()->all());

            // Redirect back with an error message
            return redirect()->route('login')->with('error', 'Authentication failed. Please try again.');
        }
    }
}
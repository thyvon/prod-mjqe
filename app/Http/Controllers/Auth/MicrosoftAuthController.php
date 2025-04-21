<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str; // For generating random passwords
use App\Services\MicrosoftTokenService;

class MicrosoftAuthController extends Controller
{
    protected MicrosoftTokenService $tokenService;

    public function __construct(MicrosoftTokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * Redirect the user to Microsoft's OAuth login page.
     */
    public function redirect(): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        return Socialite::driver('microsoft')
            ->scopes(['Sites.ReadWrite.All', 'Files.ReadWrite', 'offline_access']) // Add required scopes
            ->with(['prompt' => 'login']) // Force Microsoft to show the login page
            ->redirect();
    }

    /**
     * Refresh the Microsoft token for the authenticated user.
     */
    protected function refreshMicrosoftToken(User $user): void
    {
        try {
            $this->tokenService->refreshToken($user);
        } catch (\Exception $e) {
            Log::error('Failed to refresh Microsoft token', [
                'user_id'   => $user->id,
                'exception' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle the callback from Microsoft's OAuth.
     */
    public function callback(): \Illuminate\Http\RedirectResponse
    {
        try {
            // Retrieve the Microsoft user
            $microsoftUser = Socialite::driver('microsoft')->user();

            // Find or create the user in the database
            $user = User::updateOrCreate(
                ['email' => $microsoftUser->getEmail()],
                [
                    'name'                    => $microsoftUser->getName(),
                    'microsoft_id'            => $microsoftUser->getId(),
                    'microsoft_token'         => $microsoftUser->token,
                    'microsoft_refresh_token' => $microsoftUser->refreshToken,
                    'microsoft_token_expires' => now()->addSeconds($microsoftUser->expiresIn), // Store token expiry time
                    'password'                => bcrypt(Str::random(16)),
                ]
            );

            // Log the user in immediately
            Auth::login($user);

            // Refresh the token if necessary
            $this->refreshMicrosoftToken($user);

            // Redirect to the dashboard or desired route
            return redirect('/');
        } catch (\Exception $e) {
            Log::error('Microsoft OAuth Callback Error', [
                'exception' => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
                'request'   => request()->all(), // Log the request data for debugging
            ]);

            // Handle specific errors
            if (str_contains($e->getMessage(), 'Refresh token is invalid or expired')) {
                return redirect()->route('login')->with('error', 'Your session has expired. Please log in again.');
            }

            // Redirect back with a generic error message
            return redirect()->route('login')->with('error', 'Authentication failed. Please try again.');
        }
    }
}
<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class MicrosoftTokenService
{
    /**
     * Refresh the Microsoft access token for the given user.
     *
     * @param User $user
     * @return void
     * @throws \Exception
     */
    public function refreshToken(User $user): void
    {
        if (!$user->microsoft_refresh_token) {
            Log::warning('Refresh token is missing or invalid for user.', [
                'user_id' => $user->id,
                'email'   => $user->email,
            ]);
            throw new InvalidArgumentException('Refresh token is missing or invalid.');
        }

        $clientId = config('services.microsoft.client_id');
        $clientSecret = config('services.microsoft.client_secret');
        $tenantId = config('services.microsoft.tenant_id');

        if (!$clientId || !$clientSecret || !$tenantId) {
            Log::error('Microsoft OAuth configuration is missing.', [
                'client_id'     => $clientId,
                'client_secret' => $clientSecret ? 'set' : 'missing',
                'tenant_id'     => $tenantId,
            ]);
            throw new \Exception('Microsoft OAuth configuration is missing.');
        }

        try {
            $response = Http::asForm()->post("https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token", [
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'grant_type'    => 'refresh_token',
                'refresh_token' => $user->microsoft_refresh_token,
                'scope'         => 'offline_access Files.ReadWrite Sites.ReadWrite.All',
            ]);

            if ($response->failed()) {
                $errorBody = $response->json();
                Log::error('Failed to refresh Microsoft access token', [
                    'status' => $response->status(),
                    'error'  => $errorBody,
                    'user_id' => $user->id,
                    'email'   => $user->email,
                ]);

                // Check if the refresh token is invalid or expired
                if (isset($errorBody['error']) && $errorBody['error'] === 'invalid_grant') {
                    throw new \Exception('Refresh token is invalid or expired. User needs to reauthenticate.');
                }

                throw new \Exception('Failed to refresh Microsoft access token.');
            }

            $data = $response->json();

            $user->update([
                'microsoft_token'         => $data['access_token'],
                'microsoft_refresh_token' => $data['refresh_token'] ?? $user->microsoft_refresh_token,
                'microsoft_token_expires' => now()->addSeconds($data['expires_in']),
            ]);

            Log::info('Microsoft access token refreshed successfully.', [
                'user_id' => $user->id,
                'email'   => $user->email,
            ]);
        } catch (\Exception $e) {
            Log::error('Error refreshing Microsoft access token', [
                'exception' => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
                'user_id'   => $user->id,
                'email'     => $user->email,
            ]);
            throw $e;
        }
    }
}

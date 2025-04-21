<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MicrosoftTokenService
{
    public static function refreshToken(): void
    {
        $user = Auth::user();

        if (!$user || !$user->microsoft_refresh_token) {
            throw new \Exception('Refresh token is missing or invalid.');
        }

        try {
            $response = Http::asForm()->post('https://login.microsoftonline.com/{tenant_id}/oauth2/v2.0/token', [
                'client_id'     => config('services.microsoft.client_id'),
                'client_secret' => config('services.microsoft.client_secret'),
                'grant_type'    => 'refresh_token',
                'refresh_token' => $user->microsoft_refresh_token,
                'scope'         => 'https://graph.microsoft.com/.default',
            ]);

            if ($response->failed()) {
                $errorBody = $response->json();
                Log::error('Failed to refresh Microsoft access token', [
                    'status' => $response->status(),
                    'error'  => $errorBody,
                ]);
                throw new \Exception('Failed to refresh Microsoft access token.');
            }

            $data = $response->json();

            $user->update([
                'microsoft_token'         => $data['access_token'],
                'microsoft_refresh_token' => $data['refresh_token'] ?? $user->microsoft_refresh_token,
                'microsoft_token_expires' => now()->addSeconds($data['expires_in']),
            ]);
        } catch (\Exception $e) {
            Log::error('Error refreshing Microsoft access token', [
                'exception' => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}

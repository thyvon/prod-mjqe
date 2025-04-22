<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\MicrosoftTokenService;
use Illuminate\Support\Facades\Log;

class RefreshMicrosoftTokensCommand extends Command
{
    protected $signature = 'microsoft:refresh-tokens';
    protected $description = 'Refresh Microsoft tokens before they expire';

    protected MicrosoftTokenService $tokenService;

    public function __construct(MicrosoftTokenService $tokenService)
    {
        parent::__construct();
        $this->tokenService = $tokenService;
    }

    public function handle(): void
    {
        // Find users whose tokens are expiring within the next 5 minutes
        $users = User::whereNotNull('microsoft_refresh_token')
            ->where('microsoft_token_expires', '<', now()->addMinutes(5))
            ->get();

        foreach ($users as $user) {
            try {
                $this->tokenService->refreshToken($user);
                $this->info("✅ Refreshed token for: {$user->email}");
            } catch (\Exception $e) {
                Log::error("❌ Failed to refresh token for {$user->email}", [
                    'error' => $e->getMessage(),
                ]);
                $this->error("❌ Error refreshing token for: {$user->email}");
            }
        }

        \Log::info('🔁 RefreshMicrosoftTokensCommand ran at ' . now());
    }
}


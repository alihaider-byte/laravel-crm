<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ServiceAccountTokenManager
{
    /**
     * Get the cached service account token, or generate a new one.
     */
    public function getToken(): ?string
    {
        $cachedToken = Cache::get(config('crm.token_cache.key'));

        if ($cachedToken && isset($cachedToken['token'])) {
            Log::info('Using cached service account token');
            return $cachedToken['token'];
        }

        Log::info('No cached token found, generating new service account token');
        return $this->refreshToken();
    }

    /**
     * Force a new login and cache the token.
     */
    public function refreshToken(): ?string
    {
        try {
            $credentials = config('crm.service_account');
            
            Log::info('Attempting service account login', ['email' => $credentials['email']]);

            $response = Http::post(url('/api/v1/login'), [
                'email' => $credentials['email'],
                'password' => $credentials['password'],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $token = $data['data']['token'] ?? null;

                if ($token) {
                    $tokenData = [
                        'token' => $token,
                        'created_at' => now()->toIso8601String(),
                        'user_id' => $data['data']['user']['id'] ?? null,
                    ];

                    Cache::put(
                        config('crm.token_cache.key'),
                        $tokenData,
                        now()->addMinutes(config('crm.token_cache.ttl'))
                    );

                    Log::info('Service account token cached successfully');
                    return $token;
                }
            }

            Log::error('Service account login failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Exception during service account login', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Clear the cached token.
     */
    public function clearToken(): void
    {
        Cache::forget(config('crm.token_cache.key'));
        Log::info('Service account token cache cleared');
    }

    /**
     * Get token info from cache.
     */
    public function getTokenInfo(): ?array
    {
        return Cache::get(config('crm.token_cache.key'));
    }
}

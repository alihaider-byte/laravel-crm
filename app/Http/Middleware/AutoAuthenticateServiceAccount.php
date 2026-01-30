<?php

namespace App\Http\Middleware;

use App\Services\ServiceAccountTokenManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AutoAuthenticateServiceAccount
{
    protected ServiceAccountTokenManager $tokenManager;

    public function __construct(ServiceAccountTokenManager $tokenManager)
    {
        $this->tokenManager = $tokenManager;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip if request already has Authorization header
        if ($request->hasHeader('Authorization')) {
            Log::debug('Request has Authorization header, bypassing auto-auth');
            return $next($request);
        }

        // Skip for login endpoint
        if ($request->is('api/v1/login')) {
            return $next($request);
        }

        // Get service account token
        $token = $this->tokenManager->getToken();

        if (!$token) {
            Log::error('Failed to obtain service account token');
            return response()->json([
                'message' => 'Service unavailable - authentication failed',
                'error' => 'Unable to authenticate service account',
            ], 503);
        }

        // Inject token into request
        $request->headers->set('Authorization', "Bearer {$token}");
        Log::debug('Service account token injected into request');

        return $next($request);
    }
}

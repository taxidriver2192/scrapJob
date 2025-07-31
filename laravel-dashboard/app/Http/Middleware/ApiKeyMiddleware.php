<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ApiKeyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('X-API-Key') ?? $request->input('api_key');
        
        // You can store your API keys in config or database
        // For now, using environment variable
        $validApiKey = env('API_KEY', 'your-default-api-key-here');
        
        if (!$apiKey || $apiKey !== $validApiKey) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Valid API key is required. Include X-API-Key header or api_key parameter.'
            ], 401);
        }

        return $next($request);
    }
}

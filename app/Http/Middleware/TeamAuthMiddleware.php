<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TeamAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('Authorization');
        
        // Remove "Bearer " prefix if present
        if (str_starts_with($token, 'Bearer ')) {
            $token = substr($token, 7);
        }

        $validToken = env('TEAM_AUTH_TOKEN', 'pokemon-master-2026');

        if ($token !== $validToken) {
            return response()->json([
                'error' => 'Unauthorized',
                'error_message' => 'Invalid or missing authorization token',
            ], 401);
        }

        return $next($request);
    }
}
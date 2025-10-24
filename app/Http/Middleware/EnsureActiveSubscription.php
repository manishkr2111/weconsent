<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnsureActiveSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return response()->json([
                'status' => false,
                'message' => 'You must be logged in.'
            ], 401);
        }
        $user = Auth::user();
        $activeSubscription = $request->user()->detail;
        
        if ($activeSubscription->subscription_status != 'active') {
            return response()->json([
                'status' => false,
                'message' => 'Your subscription is not active.'
            ], 403);
        }
        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;


class EnsureIdVerified
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
            ], 401); // 401 Unauthorized
        }
        if (!Auth::user()->id_verified) {
            return response()->json([
                'status' => false,
                'message' => 'Your ID not verified yet.'
            ], 403); // 403 Forbidden
            //abort(403, 'Your ID must be verified to access this page.');
        }

        return $next($request);
    }
}
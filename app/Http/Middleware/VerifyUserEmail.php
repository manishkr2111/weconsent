<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerifyUserEmail
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // Define the list of allowed emails
        $allowedEmails = ['manishkumar@ibarts.in', 'admin@gmail.com','manishkumaribarts@gmail.com'];

        // Check if the user's email is in the allowed emails array
        if ($user && !in_array($user->email, $allowedEmails)) {
            return response()->json([
                'status' => false,
                'message' => 'You are not authorized to access this route.',
            ], 403);
        }

        // If the email is valid, continue with the request
        return $next($request);
    }
}

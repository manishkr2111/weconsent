<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Waitlist;
use Illuminate\Support\Facades\Validator;
use App\Mail\WaitlistConfirmation;
use Illuminate\Support\Facades\Mail;


class WaitlistController extends Controller
{
    public function store(Request $request)
    {
        // Validate email format only (not uniqueness)
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 400);
        }

        // Check if email already exists
        $existing = Waitlist::where('email', $request->email)->first();

        if ($existing) {
            Mail::to($request->email)->send(new WaitlistConfirmation($request->email));
            return response()->json([
                'success' => false,
                'message' => 'You are already added to the waitlist. We’ll notify you when WeConsent launches!',
            ], 200);
        }

        // Save new email
        Waitlist::create([
            'email' => $request->email,
        ]);

        Mail::to($request->email)->send(new WaitlistConfirmation($request->email));

        // Success response
        return response()->json([
            'success' => true,
            'message' => 'Thank you for joining the waitlist! We’ll notify you when WeConsent launches.',
        ], 200);
    }
}

<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Mail\OtpMail; 
use App\Mail\ResetPasswordMail;

use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessTokenResult;
use Carbon\Carbon;


class AuthController extends Controller
{
    // Helper function to generate and send OTP
    private function sendOtp($user)
    {
        // Generate OTP
        $otp = rand(100000, 999999);
        
        // Store OTP in the user model or temporary table (Optional)
        $user->otp = $otp;
        $user->otp_expires_at = now()->addMinutes(10); // OTP expires in 10 minutes
        $user->save();

        // Send OTP to user's email
        try {
            // Send OTP to the user's email
            Mail::to($user->email)->send(new OtpMail($otp));

            return response()->json([
                'success' => true,
                'message' => 'OTP sent to your email.'
            ], 200);
        } catch (\Exception $e) {
            // If there is an error sending the email, handle it
            return response()->json([
                'message' => 'Failed to send OTP email. Please try again later.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Register User and send OTP email
    public function register(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'dob'      => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // Create User
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'email_verified_at' => null, // Set email as unverified initially
        ]);
        
        $detail = UserDetail::create([
            'user_id'  => $user->id,
            'user_name'=> substr($request->email, 0, 6) . $user->id,
            'dob'      => \Carbon\Carbon::parse($request->dob)->format('Y-m-d'),
        ]);

        // Send OTP to the user's email
        return $this->sendOtp($user);  // Reusing the OTP function
    }

    // Login User and send OTP email
    public function login(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }
        $blockedUser = User::where('email',$request->email)->where('status','blocked')->first();
        if ($blockedUser) {
            return response()->json(['errors' => 'Your account has been blocked'], 401);
        }

        $email_exists = User::where('email', $request->email)->first();

        if ($email_exists) {
            // Attempt to log in
            if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
                $user = Auth::user();
                
                // Delete all previous tokens before issuing a new login token
                $user->tokens()->delete();
                // Send OTP to the user's email after successful login
                return $this->sendOtp($user);  // Reusing the OTP function
            }

            return response()->json([
                'status' => 'failed', 
                'message' => 'Invalid credentials'
            ], 401);
        } else {
            return response()->json([
                'status' => 'failed', 
                'message' => 'User not found. Please create an account first.'
            ], 404);
        } 
    }
    
    // logout
    public function logout(Request $request)
    {
        //auth()->user()->tokens()->delete();  //delete all token
        // Delete only the current token
        auth()->user()->currentAccessToken()->delete();
        return response()->json([
            'status' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    // Verify OTP
    public function verifyOtp(Request $request)
    {
        // Validate OTP
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|numeric',
            'type'=>'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // Check if user exists
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Check OTP validity and expiration
        if ($user->otp == $request->otp && now()->lessThan($user->otp_expires_at)) {
            // Update email verification status
            $user->email_verified_at = now();
            $user->save();
            if($request->type == 'login'){
                $token = $user->createToken('WeConsent')->plainTextToken;
				return response()->json([
					'status' => 'success', 
					'message' => 'Login successful',
					'user_data' => $user,
					'token' => $token
				]);
            }
            return response()->json(['message' => 'Email verified successfully']);
        }

        return response()->json(['message' => 'Invalid OTP or OTP expired'], 400);
    }
    
    // Resend OTP to a user
    public function resendOtp(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // Check if user exists
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Check if the OTP is expired or hasn't been generated
        if ($user->otp_expires_at && now()->greaterThanOrEqualTo($user->otp_expires_at)) {
            // OTP expired, resend a new OTP
            return $this->sendOtp($user); // Reusing the OTP generation and sending logic
        }

        return response()->json(['message' => 'OTP is still valid. Please check your email.'], 400);
    }



    public function login_old(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }
		
		$email_verified_at = User::where('email',$request->email)->pluck('email_verified_at')->first();
		//dd($email_verified_at);
		if($email_verified_at){
        // Attempt to log in
			if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
				
				$user = Auth::user();
				$token = $user->createToken('YourAppName')->plainTextToken;
				return response()->json([
					'status' => 'success', 
					'message' => 'Login successful',
					'user_date' => $user,
					'token' => $token
				]);
			}

			return response()->json([
				'status' => 'failed', 
				'message' => 'Invalid credentials'
			], 401);
		}else{
			return response()->json([
				'status' => 'failed', 
				'message' => 'email not verified yet'
			], 422);
		}
    }
    
    
    // Forgot Password - Send Reset Link via Email
    public function forgotPassword(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // Get the user by email
        $user = User::where('email', $request->email)->first();

        // Generate Password Reset Token
        $token = Str::random(60); // Create a unique token

        // Store the token in the remember_token field
        $user->remember_token = $token;
        $user->save();

        // Send the reset link to user's email
        try {
            // Send reset password email with the token
            $resetLink = url(route('resetPasswordForm', ['token' => $token, 'email' => $request->email]));
             Mail::to($request->email)->send(new ResetPasswordMail($resetLink));
            //Mail::to($request->email)->send(new ResetPasswordMail($token));

            return response()->json([
                'status'=>true,
                'message' => 'Password reset link has been sent to your email.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'=>false,
                'message' => 'Failed to send password reset email. Please try again later.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // reset password view
    public function resetPasswordForm(Request $request)
    {
        return view('emails.reset_password_web');
    }
    // Reset Password
    public function resetPassword(Request $request)
    {
         //dd($request->all());
        // Validate request
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // Check if the reset token matches the user's remember_token
        $user = User::where('email', $request->email)->first();

        if (!$user || $user->remember_token !== $request->token) {
            return back()->withErrors(['message' => 'Invalid token.'])->withInput();
            return response()->json(['message' => 'Invalid token.'], 400);
        }

        // Reset the user password
        $user->password = Hash::make($request->password);
        $user->remember_token = null; // Clear the remember token after password reset
        $user->save();

        return redirect()->route('resetPasswordForm', ['token' => $request->token])
                     ->with('success', 'Password has been successfully reset.');
        return redirect()->route('resetPasswordForm')->with('success', 'Password has been successfully reset.');
        
        return response()->json([
            'status'=>'success',
            'message' => 'Password has been successfully reset.'
        ], 200);
    }
}
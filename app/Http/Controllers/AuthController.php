<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    // Show the registration form
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    // Handle the registration request
    public function register(Request $request)
    {
        // Validate incoming request
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Create the user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Log the user in after registration
        Auth::login($user);

        return redirect()->intended('/home');
    }

    // Show the login form
    public function showLoginForm()
    {
        return view('auth.login');
    }

    // Handle the login request
    public function login(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:8',
        ]);

        // Attempt to log in the user
        if (Auth::attempt([
            'email' => $request->email,
            'password' => $request->password,
        ], $request->remember)) {
            
            return redirect('/admin/dashboard');
            return redirect()->intended('/home');
        }

        // If authentication fails
        return back()->withErrors(['email' => 'The provided credentials are incorrect.']);
    }

    // Log the user out
    public function logout()
    {
        Auth::logout();
        return redirect('/login');
    }
}

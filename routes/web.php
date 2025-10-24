<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;
use App\Mail\OtpVerificationMail;
use App\Mail\ConsentRequestNotification;

use App\Http\Controllers\AdminController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ConsentRequestController;
use App\Http\Controllers\websiteSettingsController;

use App\Http\Middleware\VerifyUserEmail;

Route::get('/test-email', function () {
    // Test data: You can use any email you like to test the mail functionality
    $email = 'manishkumar@ibarts.in';
    $otp = rand(100000, 999999); // Generate a random OTP

    try {
        // Send the OTP email
        $response = Mail::to($email)->send(new OtpMail($otp));

        return response()->json(['message' => 'OTP email sent successfully.', 'response'=>$response], 200);
    } catch (\Exception $e) {
        die($e->getMessage());
        // Handle error if the email fails to send
        return response()->json(['message' => 'Failed to send OTP email.', 'error' => $e->getMessage()], 500);
    }
});


Route::get('/test-email-consent', function () {

    // Test data
    $author = (object)[
        'name' => 'John Doe',
        'email' => 'manishkumar@ibarts.in',
    ];

    $receiver = (object)[
        'name' => 'Jane Smith',
        'email' => 'manishkumar@ibarts.in',
    ];

    // Instead of a real ConsentRequest model, use a simple object
    $consentRequest = (object)[
        'type' => 'Intimacy',
        'sender' => $author,
        'receiver' => $receiver,
    ];

    try {
        // Send email to author
        Mail::to($author->email)->queue(new ConsentRequestNotification($consentRequest, 'author', 'accepted'));

        // Send email to receiver
        Mail::to($receiver->email)->queue(new ConsentRequestNotification($consentRequest, 'receiver', 'accepted'));

        return response()->json(['message' => 'Test emails sent successfully.'], 200);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Failed to send test emails.', 'error' => $e->getMessage()], 500);
    }
});


Route::get('/test', function () {
    return view('chat');
});
Route::get('/qrscan', function () {
    return view('qrscan');
});

Route::get('/', function () {
    return redirect('/login');
});

// Show the registration form
Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register');

// Handle the registration form submission
Route::post('/register', [AuthController::class, 'register']);

// Show the login form
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');

// Handle the login form submission
Route::post('/login', [AuthController::class, 'login']);

// Logout Route
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/reset-password/{token}', [\App\Http\Controllers\Api\AuthController::class, 'resetPasswordForm'])->name('resetPasswordForm');
Route::post('/reset-password', [\App\Http\Controllers\Api\AuthController::class, 'resetPassword'])->name('resetPassword');

// Protected Route (requires authentication)
Route::middleware('auth')->get('/home', function () {
    return view('home');  // A view for logged-in users
});

Route::middleware(['auth'])->group(function () {
    Route::post('/sumsub/applicant', [\App\Http\Controllers\Api\UserDetailController::class, 'createApplicant']);
    
    Route::get('/sumsub/applicant/{applicantId}/status', [\App\Http\Controllers\Api\UserDetailController::class, 'getApplicantStatus']);
    
    // Generate access token for SDK
    Route::post('/sumsub/access-token', [\App\Http\Controllers\Api\UserDetailController::class, 'generateAccessToken']);
    
    // Retrieve all fixedInfo and info stored for the applicant.
    Route::get('/sumsub/applicant-info/{applicantId}', [\App\Http\Controllers\Api\UserDetailController::class, 'getApplicantInfo']);
    
    Route::get('/verification', [\App\Http\Controllers\Api\UserDetailController::class, 'showVerificationPage'])->name('verification.show');
    Route::post('/sumsub/access-token', [\App\Http\Controllers\Api\UserDetailController::class, 'generateAccessTokenWeb'])->name('sumsub.token');
});

Route::middleware('auth')->group(function () {
        Route::get('/admin/profile', [AdminController::class, 'profile'])->name('admin.profile');
        Route::put('/admin/profile', [AdminController::class, 'updateProfile'])->name('admin.profile.update');


        Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('dashboard')->middleware(VerifyUserEmail::class);
        
        // User Management Routes
        Route::get('admin/users',[UserController::class,'index'])->name('users')->middleware(VerifyUserEmail::class);
        Route::get('admin/blocked-users',[UserController::class,'blockerdUsers'])->name('blockerdUsers')->middleware(VerifyUserEmail::class);
        // Single route for edit + update + block
        Route::match(['get', 'put'], '/admin/users/{user}', [UserController::class, 'editOrUpdate'])->name('users.editOrUpdate');

        Route::get('/admin/subscriptions', [AdminController::class, 'subscriptionList'])->name('subscriptions.index');
        Route::get('/admin/active/subscriptions', [UserController::class, 'activeSubscriptionList'])->name('users.subscriptions');

        Route::get('/admin/consent-requests',[AdminController::class,'consentRequests'])->name('consentRequests')->middleware(VerifyUserEmail::class);
        Route::match(['get', 'put'], '/admin/consent-requests/{consentRequest}', [AdminController::class, 'editOrUpdate'])->name('consentRequests.editOrUpdate');
        
        Route::get('/admin/qrcodes', [AdminController::class, 'QrCodes'])->name('qrcodes.index');

        Route::get('/settings/{id}/edit', [websiteSettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings/{id}', [websiteSettingsController::class, 'update'])->name('settings.update');
});



Route::get('/admin', function () {
    return view('admin_test');
});
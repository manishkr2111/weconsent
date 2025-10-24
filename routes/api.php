<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserDetailController;
use App\Http\Controllers\Api\ConsentRequestController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\UserSearchController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\WaitlistController;
//use App\Http\Middleware\EnsureIdVerified;

Route::get('/user-test', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum')->middleware('verified.id')->middleware('active.subscription');

Route::get('/allconsentrequests', [ConsentRequestController::class, 'allconsentrequests']);


// User Registration Route
Route::post('/register', [AuthController::class, 'register']);

// User Login Route
Route::post('/login', [AuthController::class, 'login']);

// OTP Verification Route
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
Route::post('forgot-password', [AuthController::class, 'forgotPassword']);

Route::post('/join-waitlist', [WaitlistController::class, 'store']);
// ID verification
Route::post('/sumsub/webhook', [UserDetailController::class, 'handleWebhook']);
Route::middleware('auth:sanctum')->group(function () {

    // Create applicant
    Route::post('/sumsub/applicant', [UserDetailController::class, 'createApplicant']);

    // Get applicant status
    Route::get('/sumsub/applicant/{applicantId}/status', [UserDetailController::class, 'getApplicantStatus']);

    // Generate access token for SDK
    Route::post('/sumsub/access-token', [UserDetailController::class, 'generateAccessToken']);

    // Retrieve all fixedInfo and info stored for the applicant.
    Route::get('/sumsub/applicant-info/{applicantId}', [UserDetailController::class, 'getApplicantInfo']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user/details', [UserDetailController::class, 'show']);       // view details
    Route::get('/user/{id}', [UserDetailController::class, 'getUserDetails']);       // view details
    //Route::post('/user/details/create', [UserDetailController::class, 'create']);    // create new details
    Route::post('/user/details/update', [UserDetailController::class, 'update']);     // update details

    Route::get('/recent-activities', [UserDetailController::class, 'RecentActivity']);

    Route::post('generate/qrcode', [UserDetailController::class, 'GenerateQRcode'])
        ->middleware('verified.id')->middleware('active.subscription');



    Route::get('/search-users', [UserSearchController::class, 'search']);
    Route::get('/discover-random', [UserSearchController::class, 'discoverRandom']);


    Route::middleware(['verified.id', 'active.subscription'])->group(function () {
        //consent apis
        Route::post('/consent-request/create', [ConsentRequestController::class, 'store']);
        Route::post('/consent-request/accept', [ConsentRequestController::class, 'accept']);
        Route::post('/consent-request/accept-intimacy', [ConsentRequestController::class, 'acceptIntimacyRequest']);
        Route::post('/consent-request/consent-action', [ConsentRequestController::class, 'consentRequestAction']);

        Route::post('/consent/otp/send', [ConsentRequestController::class, 'sendOtp']);
        Route::post('/consent/otp/verify', [ConsentRequestController::class, 'verifyOtp']);
    });

    Route::any('/consent-request/created', [ConsentRequestController::class, 'getCreatedRequests']);
    // Route for getting all received requests by a user
    Route::any('/consent-request/received', [ConsentRequestController::class, 'getReceivedRequests']);


    Route::post('/consent-request/ConsentOtp', [ConsentRequestController::class, 'ConsentOtp']);

    Route::get('/connected-users', [ChatController::class, 'connectedUsers']);


    // payments
    Route::post('/create-checkout-session', [SubscriptionController::class, 'createCheckoutSession']);


    Route::middleware(['verified.id', 'active.subscription'])->group(function () { // chat
        Route::post('/chat-send', [ChatController::class, 'sendMessage']);
        Route::get('/chat/{userId}', [ChatController::class, 'getMessages']);
        Route::get('/all-chat', [ChatController::class, 'getConversations']);
    });
});
Route::post('/chat-send-test', [ChatController::class, 'sendMessage']);
//Route::any('/mark-as-read/{userId}', [ChatController::class, 'readchat']);


Route::post('/webhook-stripe', [SubscriptionController::class, 'webhook']);

//cron to update user subscription
Route::get('/stripe/status-update', [SubscriptionController::class, 'updateStripeStatus']);

// routes/api.php
//Route::post('/consent/store', [ConsentRequestController::class, 'store'])->middleware('auth:sanctum');

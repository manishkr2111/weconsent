<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConsentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Mail\ConsentOTPEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Exception;
use App\Models\User;
use App\Models\chatMessage;
use App\Models\UserConnection;
use App\Models\QRCode;
use App\Models\RecentActivity;

class ConsentRequestController extends Controller
{
    
    public function allconsentrequests(){
        $allconsentrequests = ConsentRequest::paginate(2);
        return response()->json([
                'message' => $allconsentrequests
            ], 200);

    }
    
    /**
     * Store a newly created consent request.
     */
    public function store(Request $request)
    {
        
        $validDateTypes = [
            'dinner',
            'coffee_Tea',
            'movie',
            'drinks',
            'concert_Live_Music',
            'walk_Park',
            'museum_Exhibition',
            'activity_Game',
            'home_Visit',
            'trip_travel',
            'other'
        ];
        $validIntimacyTypes = [
            'kissing',
            'touching_cuddling',
            'staying_over_sharing_bed',
            'sexual_activity_general',
            'inviting_to_private_residence',
            'other'
        ];
    
        // Validate incoming request data
        $rules = [
            'verification_token' => 'required|string',
            'created_by' => 'required|exists:users,id', // Ensure created_by is an existing user ID
            'sent_to' => 'required|exists:users,id', // Ensure sent_to is an existing user ID
            'consent_type' => 'required|in:chat,connection,intimate,date',
            'date_type' => ['nullable', Rule::in($validDateTypes)],
            'intimacy_type' => ['nullable', Rule::in($validIntimacyTypes)],
            'other_type_description' => 'nullable|string',
            'sent_otp' => 'nullable|size:6',
            'accept_otp' => 'nullable|size:6',
            'location' => 'nullable|array',
            'event_date' => 'nullable|date',
            'event_duration' => 'nullable|numeric',
        ];
        
        if ($request->date_type === 'other') {
            $rules['other_type_description'] = 'required|string'; // Ensure other_type_description is present
        }
    
        // Validate incoming request data
        $validator = Validator::make($request->all(), $rules);

        // If validation fails, return a 400 error with the validation messages
        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 400);
        }
        
        // Get the authenticated user
        $user = Auth::user();
        // Check if the authenticated user is the same as the user who is creating the request
        if ($user->id != $request->created_by) {
            return response()->json([
                'status' => false,
                'messages' => 'Invalid/Unauthorized request',
                'data' => [],
            ], 401); 
        }
        if (!$user->detail || $user->detail->subscription_status != "active" ) {
            return response()->json([
                'status' => false,
                'messages' => 'Your subscription has ended or not subscribed yet',
                'data' => [],
            ], 401); 
        }
        if (!$user->id_verified) {
            return response()->json([
                'status' => false,
                'messages' => 'Document ID not verified yet',
                'data' => [],
            ], 401);
        }
        
        // Check if the verification token exists and is valid
        $verified = Cache::pull("consent_verified_token:{$request->verification_token}");
        if (!$verified || $verified['user_id'] != $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'Verification token expired or invalid',
            ], 403);
        }
        
        if($request->consent_type == 'date'){
            if(!$request->date_type){
                return response()->json([
                    'status' => false,
                    'messages' => 'date type required',
                    'data' => [],
                ], 400);
            }
            $request->intimacy_type = null;
        }elseif($request->consent_type == 'intimate'){
            if(!$request->intimacy_type){
                return response()->json([
                    'status' => false,
                    'messages' => 'intimacy type required',
                    'data' => [],
                ], 400);
            }
            $request->date_type = null;
        }else{
            $request->intimacy_type = null;
            $request->date_type = null;
            $request->other_type_description = null;
        }
        
       
        
        try {
            $existingConnection = connectionExists($request->sent_to, $user->id);
            
            if ($request->consent_type != 'connection') {
                if(!$existingConnection || $existingConnection->status != 'accepted'){
                    return response()->json([
                            'status' => false,
                            'message' => 'You are not connected yet to this user or blocked by this user.',
                            'data' => [],
                        ], 403);
                }
                // Create the consent request
                $consentRequest = ConsentRequest::create([
                    'created_by' => $request->created_by,
                    'sent_to' => $request->sent_to,
                    'consent_type' => $request->consent_type,
                    'date_type' => $request->date_type,
                    'intimacy_type' => $request->intimacy_type,
                    'other_type_description' => $request->other_type_description,
                    'status' => 'pending',
                    'location' => $request->location, // Save the location as JSON
                    'event_date' => Carbon::parse($request->event_date), // Save the date as timestamp or string
                    'event_duration' => $request->event_duration,
                ]);
                
                if($request->consent_type == 'intimate'){
                   $intimacyCode = (string)random_int(100000, 99999999) .$consentRequest->id;
                   $consentRequest-> intimacy_code = $intimacyCode;
                   $consentRequest->save();
                }
                
                // send notification mail
                $response = sendConsentRequestEmails($user->id, $request->sent_to, $request->consent_type, 'created');
            }
            
              //
            // Check if the consent type is "connection" and create the connection request
            if ($request->consent_type == 'connection') {
    
                if ($existingConnection) {
                    if ($existingConnection->status == 'blocked') {
                        return response()->json([
                            'status' => false,
                            'message' => 'You are blocked by this user. Connection request cannot be created.',
                            'data' => [],
                        ], 403);
                    }
                    if($existingConnection->status == 'pending'){
                        return response()->json([
                            'status' => false,
                            'message' => 'connection request already exist.',
                            'data' => [],
                        ], 400);
                    }
                    return response()->json([
                        'status' => false,
                        'message' => 'You are already connected to this user.',
                        'data' => [],
                    ], 400);
                }
    
                $consentConectionRequest = ConsentRequest::create([
                    'created_by' => $request->created_by,
                    'sent_to' => $request->sent_to,
                    'consent_type' => $request->consent_type,
                    'status' => 'pending',
                ]);
                Log::info('$consentConectionRequest: ' . $consentConectionRequest);
                // If no existing connection, create a new connection request
                $connection = UserConnection::create([
                    'sender_id' => $request->created_by, // The user who sent the request
                    'receiver_id' => $request->sent_to,  // The user who received the request
                    'consent_id' => $consentConectionRequest->id,
                    'status' => 'pending', // The connection request is pending
                ]);
                
                // send notification mail
                $response = sendConsentRequestEmails($user->id, $request->sent_to, $request->consent_type, 'created');
                
                RecentActivity::create([
                    'user_id' => $user->id, // or any user id
                    'action' => 'Create',
                    'details' => 'Consent Request Created',
                    'type' => 'Consent Request', // optional category
                ]);
                
                return response()->json([
                    'status' => true,
                    'message' => 'Consent request created successfully',
                    'data' => $consentConectionRequest,
                ], 200);
            }
            
            RecentActivity::create([
                'user_id' => $user->id, // or any user id
                'action' => 'Create',
                'details' => 'Consent Request Created',
                'type' => 'Consent Request', // optional category
            ]);
            // Return success response
            return response()->json([
                'status' => true,
                'message' => 'Consent request created successfully',
                'data' => $consentRequest,
            ], 200);
        } catch (QueryException $e) {
            // Handle database-related errors
            return response()->json([
                'status' => false,
                'message' => 'Database error occurred: ' . $e->getMessage(),
            ], 500);
        } catch (Exception $e) {
            // Handle any other errors
            return response()->json([
                'status' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    ////  accept request
    public function accept(Request $request)
    {
        // Validate incoming request data
        $validator = Validator::make($request->all(), [
            'verification_token' => 'required|string',
            'consent_request_id' => 'required|exists:consent_requests,id', // Make sure the consent request exists
            //'accept_otp' => 'required|size:6', // OTP should be a 6-digit code
            'action' => 'required|in:accept,reject',
        ]);

        // If validation fails, return a 400 error with the validation messages
        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 400);
        }
        
        
        $user = Auth::user();
        
        $verified = Cache::pull("consent_verified_token:{$request->verification_token}");
        if (!$verified || $verified['user_id'] != $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'Verification token expired or invalid',
            ], 403);
        }
        
        // Retrieve the consent request
        $consentRequest = ConsentRequest::where('id',$request->consent_request_id)->where('sent_to',$user->id)->first();
        /*$consentRequest = ConsentRequest::where('id', $request->consent_request_id)
                        ->where(function ($q) use ($user) {
                            $q->where('created_by', $user->id)
                              ->orWhere('sent_to', $user->id);
                        })
                        ->first(); */
                       
        
        $existingConnection = connectionExists($consentRequest->created_by, $user->id);
        //dd($existingConnection);       
        if (!$existingConnection || $existingConnection->status != 'accepted' ) {
            if($consentRequest && $consentRequest->consent_type == 'date'){
                return response()->json([
                        'status' => false,
                        'message' => "You are not connected yet to this user"
                    ], 403);
            }
        }
        if($consentRequest){
            if($consentRequest && $consentRequest->consent_type == 'intimate')
            {
                 return response()->json([
                        'status' => false,
                        'message' => "Please use scanner to accept the intimacy request",
                        'data' => [],
                    ], 400);
            }
            if($consentRequest && $consentRequest->consent_type != 'intimate'){
                if (in_array($consentRequest->status, ['accepted','rejected','cancelled','expired'])) {
                    return response()->json([
                        'status' => false,
                        'message' => "Consent request already {$consentRequest->status}",
                        'data' => [],
                    ], 400);
                }
            }
            // Verify if the OTP matches
            /*
            if ($consentRequest->accept_otp !== $request->accept_otp) {
                return response()->json([
                    'status' =>  false,
                    'message' => 'Invalid OTP',
                    'data' => [],
                ], 400);
            }
            */
        //if($consentRequest){
            if (in_array($consentRequest->status, ['accepted','rejected','cancelled','expired'])) {
                return response()->json([
                    'status' => false,
                    'message' => "Consent request already {$consentRequest->status}",
                    'data' => [],
                ], 400);
            }
        
            if ($request->action == 'accept') {
                $consentRequest->status = 'accepted';
                //$consentRequest->accept_otp_verified_at = now();
                $consentRequest->accept_or_rejected_at = now();
                $consentRequest->save();
                
                if($consentRequest->consent_type == 'connection'){
                    $existingConnection->status = 'accepted';
                    $existingConnection->save();
                }
                
                // send notification mail
                $response = sendConsentRequestEmails($consentRequest->created_by, $user->id, $consentRequest->consent_type, 'accepted');
                RecentActivity::create([
                    'user_id' => $user->id, // or any user id
                    'action' => 'Accept',
                    'details' => 'Consent Request accepted',
                    'type' => 'Consent Request', // optional category
                ]);
                // Return success response
                return response()->json([
                    'status' =>  true,
                    'message' => 'Consent request accepted successfully'
                ], 200);
            } elseif($request->action == 'reject') {
                $consentRequest->status = 'rejected';
                $consentRequest->accept_or_rejected_at = now(); // add this column if needed
                $consentRequest->save();
                // send notification mail
                $response = sendConsentRequestEmails($consentRequest->created_by, $user->id, $consentRequest->consent_type, 'rejected');
                RecentActivity::create([
                    'user_id' => $user->id, // or any user id
                    'action' => 'Reject',
                    'details' => 'Consent Request rejected',
                    'type' => 'Consent Request', // optional category
                ]);
                return response()->json([
                    'status' =>  true,
                    'message' => 'Consent request rejected successfully'
                ], 200);
            }elseif($request->action == 'complete'){
                $consentRequest->status = 'completed';
                $consentRequest->accept_or_rejected_at = now(); // add this column if needed
                $consentRequest->save();
                // send notification mail
                $response = sendConsentRequestEmails($consentRequest->created_by, $user->id, $consentRequest->consent_type, 'completed');
                RecentActivity::create([
                    'user_id' => $user->id, // or any user id
                    'action' => 'Complete',
                    'details' => 'Consent Request complete',
                    'type' => 'Consent Request', // optional category
                ]);
                return response()->json([
                    'status' =>  true,
                    'message' => 'Consent request marked as completed'
                ], 200);
            }
            
            return response()->json([
                'status' => false,
                'message' => 'Invalid action specified',
                'data' => []
            ], 400);
        }else{
            return response()->json([
                'status' =>  false,
                'message' => 'Consent request not exists',
                'data' => $consentRequest,
            ], 400);
        }
    }
    
    // accept intimacy request
    public function acceptIntimacyRequest(Request $request)
    {
        // Validate incoming request data
        $validator = Validator::make($request->all(), [
            //'verification_token' => 'required|string',
            "consent_id"=>'required|exists:consent_requests,id',
            'consent_request_token' => 'required|string', // Make sure the consent request exists
            'qrcode_user_id' => 'required|exists:users,id',
            'action' => 'required|in:accept,reject',
        ]);

        // If validation fails, return a 400 error with the validation messages
        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 400);
        }
        
        $user = Auth::user();
        $qrcodeuser = User::find($request->qrcode_user_id);
        $QRCode = QRCode::where('user_id',$request->qrcode_user_id)->first();
        if(!$QRCode){
            return response()->json([
                'status' => false,
                'message' => "Qr Code invalid or not exists",
                'data' => [],
            ], 400);
        }
        
        $existingConnection = connectionExists($qrcodeuser->id, $user->id);
        //dd($existingConnection);       
        if (!$existingConnection || $existingConnection->status !="accepted") {
            return response()->json([
                    'status' => false,
                    'message' => "You are not connected yet to this user"
                ], 403);
        }
        // Retrieve the consent request
        $consentRequest = ConsentRequest::where('id',$request->consent_id)->where('intimacy_code',$request->consent_request_token)
                        ->where('created_by',$qrcodeuser->id)
                        ->where('sent_to',$user->id)
                        ->first();

        if($consentRequest){
            if (in_array($consentRequest->status, ['accepted','rejected','cancelled','expired'])) {
                return response()->json([
                    'status' => false,
                    'message' => "Consent request already {$consentRequest->status}",
                    'data' => [],
                ], 400);
            }
        
            if ($request->action == 'accept') {
                $consentRequest->status = 'accepted';
                //$consentRequest->accept_otp_verified_at = now();
                $consentRequest->accept_or_rejected_at = now();
                $consentRequest->save();
                
                $response = sendConsentRequestEmails($consentRequest->created_by, $user->id, $consentRequest->consent_type, 'accepted');
                RecentActivity::create([
                    'user_id' => $user->id, // or any user id
                    'action' => 'Accept',
                    'details' => 'Consent Request accepted',
                    'type' => 'Consent Request', // optional category
                ]);
                // Return success response
                return response()->json([
                    'status' =>  true,
                    'message' => 'Consent request accepted successfully'
                ], 200);
            } elseif($request->action == 'reject') {
                $consentRequest->status = 'rejected';
                $consentRequest->accept_or_rejected_at = now(); // add this column if needed
                $consentRequest->save();
                $response = sendConsentRequestEmails($consentRequest->created_by, $user->id, $consentRequest->consent_type, 'rejected');
                RecentActivity::create([
                    'user_id' => $user->id, // or any user id
                    'action' => 'Reject',
                    'details' => 'Consent Request rejected',
                    'type' => 'Consent Request', // optional category
                ]);
                return response()->json([
                    'status' =>  true,
                    'message' => 'Consent request rejected successfully'
                ], 200);
            }elseif($request->action == 'complete'){
                $consentRequest->status = 'completed';
                $consentRequest->accept_or_rejected_at = now(); // add this column if needed
                $consentRequest->save();
                $response = sendConsentRequestEmails($consentRequest->created_by, $user->id, $consentRequest->consent_type, 'completed');
                RecentActivity::create([
                    'user_id' => $user->id, // or any user id
                    'action' => 'Complete',
                    'details' => 'Consent Request complete',
                    'type' => 'Consent Request', // optional category
                ]);
                return response()->json([
                    'status' =>  true,
                    'message' => 'Consent request marked as completed'
                ], 200);
            }
            
            return response()->json([
                'status' => false,
                'message' => 'Invalid action specified',
                'data' => []
            ], 400);

        }else{
            return response()->json([
                'status' =>  false,
                'message' => 'Consent request not exists / Invalid verification Code',
                'data' => $consentRequest,
            ], 400);
        }
    }
    
    
    ////
    ////  accept request
    public function consentRequestAction(Request $request)
    {
        // Validate incoming request data
        $validator = Validator::make($request->all(), [
            'verification_token' => 'required|string',
            'consent_request_id' => 'required|exists:consent_requests,id',
            'action' => 'required|in:cancel,complete,schedule',
        ]);

        // If validation fails, return a 400 error with the validation messages
        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 400);
        }
        
        $user = Auth::user();
        
        $verified = Cache::pull("consent_verified_token:{$request->verification_token}");
        if (!$verified || $verified['user_id'] != $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'Verification token expired or invalid',
            ], 403);
        }
        
        
        // Retrieve the consent request
        $consentRequest = ConsentRequest::where('id',$request->consent_request_id)->where('sent_to',$user->id)->first();
                       
        
        $existingConnection = connectionExists($consentRequest->created_by, $user->id);
        //dd($existingConnection);       
        if (!$existingConnection || $existingConnection->status != 'accepted' ) {
            if($consentRequest && $consentRequest->consent_type == 'date'){
                return response()->json([
                        'status' => false,
                        'message' => "You are not connected yet to this user"
                    ], 403);
            }
        }
        if($consentRequest){
            if (in_array($consentRequest->status, ['rejected','cancelled','expired'])) {
                return response()->json([
                    'status' => false,
                    'message' => "Consent request already {$consentRequest->status}",
                    'data' => [],
                ], 400);
            }
        
            if ($request->action == 'cancel') {
                $consentRequest->status = 'cancelled';
                //$consentRequest->accept_otp_verified_at = now();
                $consentRequest->accept_or_rejected_at = now();
                $consentRequest->save();
                $response = sendConsentRequestEmails($consentRequest->created_by, $user->id, $consentRequest->consent_type, 'cancelled');
                RecentActivity::create([
                    'user_id' => $user->id, // or any user id
                    'action' => 'Cancel',
                    'details' => 'Consent Request cancelled',
                    'type' => 'Consent Request', // optional category
                ]);
                // Return success response
                return response()->json([
                    'status' =>  true,
                    'message' => 'Consent request cancelled successfully'
                ], 200);
            } elseif($request->action == 'reject') {
                $consentRequest->status = 'rejected';
                $consentRequest->accept_or_rejected_at = now(); // add this column if needed
                $consentRequest->save();
                
                $response = sendConsentRequestEmails($consentRequest->created_by, $user->id, $consentRequest->consent_type, 'rejected');
                RecentActivity::create([
                    'user_id' => $user->id, // or any user id
                    'action' => 'Reject',
                    'details' => 'Consent Request rejected',
                    'type' => 'Consent Request', // optional category
                ]);
                return response()->json([
                    'status' =>  true,
                    'message' => 'Consent request rejected successfully'
                ], 200);
            }elseif($request->action == 'complete'){
                $consentRequest->status = 'completed';
                $consentRequest->accept_or_rejected_at = now(); // add this column if needed
                $consentRequest->save();
                
                $response = sendConsentRequestEmails($consentRequest->created_by, $user->id, $consentRequest->consent_type, 'completed');
                RecentActivity::create([
                    'user_id' => $user->id, // or any user id
                    'action' => 'Complete',
                    'details' => 'Consent Request complete',
                    'type' => 'Consent Request', // optional category
                ]);
                return response()->json([
                    'status' =>  true,
                    'message' => 'Consent request marked as completed'
                ], 200);
            }
            
            return response()->json([
                'status' => false,
                'message' => 'Invalid action specified',
                'data' => []
            ], 400);
        }else{
            return response()->json([
                'status' =>  false,
                'message' => 'Consent request not exists',
                'data' => $consentRequest,
            ], 400);
        }
    }
    
    ///  get consent_requests
    
    /**
     * Get all the consent requests created by the user.
     */
    public function getCreatedRequests(Request $request)
    {
        
        $user = Auth::user();
        //dd($user->id);
        // Retrieve all consent requests created by the user
        //$createdRequests = ConsentRequest::where('created_by', $user->id)->get();
        
        $createdRequests = ConsentRequest::where('created_by', $user->id)
                            ->with('createdBy')->with('sentTo')
                            ->orderBy('created_at', 'desc') ->paginate(10);

        $createdRequests->getCollection()->transform(function ($item) {
            unset($item['intimacy_code']);
            return $item;
        });
        // Return the data
        return response()->json([
            'status' => true,
            'message' => 'Created requests retrieved successfully',
            'total'=>$createdRequests->total(),
            'data' => $createdRequests,
        ], 200);
    }

    /**
     * Get all the consent requests received by the user.
     */
    public function getReceivedRequests(Request $request)
    {
        $user = Auth::user();

        // Retrieve all consent requests received by the user
        //$receivedRequests = ConsentRequest::where('sent_to', $user->id)->get();
        $receivedRequests = ConsentRequest::where('sent_to', $user->id)
                            ->with('createdBy')->with('sentTo')
                            ->orderBy('created_at', 'desc') ->paginate(10);
        // Return the data
        return response()->json([
            'status' => true,
            'message' => 'Received requests retrieved successfully',
            'total'=>$receivedRequests->total(),
            'data' => $receivedRequests,
        ], 200);
    }
    
    public function ConsentOtp(Request $request){
        $otp = rand(100000, 999999); // Example OTP generation

        // Get the user's name (or any other info)
        $user = Auth::user();
        $subject = "OTP for Consent Request";
        $userName = $user->name;
        $ConsentRequest = ConsentRequest::where('id',$request->id)->first();
        if($ConsentRequest){
            $ConsentRequest->accept_otp = $otp;
            $ConsentRequest->save();
        }
        dd($ConsentRequest);
        //dd($userName);
        try {
            //$response = Mail::to(Auth::user()->email)->send(new ConsentOTPEmail($otp, $user->Name, 'OTP for Consent Request'));
            $response = Mail::to($user->email)->send(new ConsentOTPEmail($otp,$userName,$subject));
            //dd($user);
            return response()->json([
                'status' => true,
                'message' => 'OTP email sent successfully.',
                'response'=>$response,
            ],200);
        } catch (\Exception $e) {
            die($e->getMessage());
            // Handle error if the email fails to send
            return response()->json([
                'status' => false,
                'message' => 'Failed to send OTP email.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
        /**
     * Send OTP (cached, not persisted).
     */
    public function sendOtp(Request $request)
    {
         /*
        $validator = Validator::make($request->all(), [
            'consent_request_id' => 'nullable|exists:consent_requests,id',
        ]);

        // If validation fails, return a 400 error with the validation messages
        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 400);
        }*/
        $user = Auth::user();
        // Throttle optional (prevent abuse)
        $key = "consent_otp_throttle:{$user->id}";
        $attempts = Cache::get($key, 0);
        if ($attempts >= 50) {
            return response()->json([
                'status' => false,
                'message' => 'Too many OTP requests. Try again later.',
            ], 429);
        }
        Cache::put($key, $attempts + 1, now()->addMinutes(10));

        // Create a 6-digit OTP
        $otp = (string)random_int(100000, 999999);

        // Store only a HASH of the OTP in cache for 5 minutes
        Cache::put("consent_otp:{$user->id}", Hash::make($otp), now()->addMinutes(5));

        // Email the OTP (no dd/exit)
        Mail::to($user->email)->send(new ConsentOTPEmail(
            $otp,
            $user->name ?? $user->email,
            'OTP for Consent Request'
        ));
        
        /*
        if($request->consent_request_id){
            $consentRequest = ConsentRequest::find($request->consent_request_id);
            if($consentRequest){
                $consentRequest->accept_otp = $otp;
                $consentRequest->save();
            }
        }*/
        //$user->accept_otp = $otp ;
        //$user->save();
        return response()->json([
            'status' => true,
            'message' => 'OTP sent to your email.',
        ], 200);
    }
    
        /**
     * Verify OTP and issue a short-lived verification token.
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|digits:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'messages' => $validator->errors(),
            ], 400);
        }

        $user = Auth::user();

        $cachedHash = Cache::get("consent_otp:{$user->id}");
        if (!$cachedHash) {
            return response()->json([
                'status' => false,
                'message' => 'OTP expired or not requested.',
            ], 400);
        }

        if (!Hash::check($request->otp, $cachedHash)) {
            // optional: track failed attempts and invalidate after N tries
            return response()->json([
                'status' => false,
                'message' => 'Invalid OTP.',
            ], 400);
        }

        // OTP correct → consume it
        Cache::forget("consent_otp:{$user->id}");

        // Issue a short-lived verification token (10 min)
        $token = Str::random(40);
        Cache::put("consent_verified_token:{$token}", ['user_id' => $user->id], now()->addMinutes(10));

        return response()->json([
            'status' => true,
            'message' => 'OTP verified.',
            'verification_token' => $token,
            'expires_in_minutes' => 10,
        ], 200);
    }
    
    public function connectedUsers_old(){
        
        $user = Auth::user();
        $UserConnections = UserConnection::where('sender_id', $user->id)
                        ->orWhere('receiver_id', $user->id)
                        ->with('sender')
                        ->with('receiver')
                        ->paginate(20);
        if($UserConnections){
            return $UserConnections;
        }
        return response()->json([
            'status' => false,
            'message' => 'No any connection yet.',
        ], 200);
    }
    
    
    
    /*
    public function connectedUsers_new()
    {
        $user = Auth::user();
        $authId = $user->id;
    
        // Get all connections
        $userConnections = UserConnection::where('sender_id', $authId)
                            ->orWhere('receiver_id', $authId)
                            ->with(['sender', 'receiver'])
                            ->get();
    
        if ($userConnections->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No connections yet.',
            ], 200);
        }
    
        $connectedUsers = [];
    
        foreach ($userConnections as $connection) {
            // Determine the connected user
            $connectedUser = $connection->sender_id == $authId ? $connection->receiver : $connection->sender;
            $userDetail = $connectedUser->detail;
            if ($userDetail) {
                $avatar = $userDetail->profile_image_url = $userDetail->profile_image
                    ? url('storage/' . $userDetail->profile_image)
                    : "";
            }
            // Get last chat message
            $lastMessage = chatMessage::where(function($q) use ($authId, $connectedUser) {
                                    $q->where('sender_id', $authId)
                                      ->where('receiver_id', $connectedUser->id);
                                })
                                ->orWhere(function($q) use ($authId, $connectedUser) {
                                    $q->where('sender_id', $connectedUser->id)
                                      ->where('receiver_id', $authId);
                                })
                                ->orderBy('created_at', 'desc')
                                ->first();
    
            $connectedUsers[] = [
                'connected_user' => $connectedUser->id,
                'name' => $connectedUser->name,
                'lastMessage' => $lastMessage ? $lastMessage->message : null,
                'timestamp' => $lastMessage ? $lastMessage->created_at->format('g:i A') : null,
                'avatar' => $avatar,
                'unreadCount' => $lastMessage ? chatMessage::where('sender_id', $connectedUser->id)
                                                ->where('receiver_id', $authId)
                                                //->where('is_read', 0)
                                                ->count() : 0,
                'isOnline' => $connectedUser->status === 'active', // adjust logic if you have real-time online status
            ];
        }
    
        return response()->json($connectedUsers);
    }
    
    public function connectedUsers_new2(Request $request)
    {
        $user = Auth::user();
        $authId = $user->id;
    
        $perPage = $request->get('per_page', 1); // default 10 per page
    
        // Get paginated connections
        $userConnections = UserConnection::where('sender_id', $authId)
            ->orWhere('receiver_id', $authId)
            ->with(['sender', 'receiver'])
            ->paginate($perPage);
    
        if ($userConnections->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No connections yet.',
            ], 200);
        }
    
        $connectedUsers = $userConnections->map(function ($connection) use ($authId) {
            $connectedUser = $connection->sender_id == $authId ? $connection->receiver : $connection->sender;
            $userDetail = $connectedUser->detail;
    
            $avatar = $userDetail && $userDetail->profile_image 
                ? url('storage/' . $userDetail->profile_image) 
                : "";
    
            $lastMessage = chatMessage::where(function($q) use ($authId, $connectedUser) {
                                    $q->where('sender_id', $authId)
                                      ->where('receiver_id', $connectedUser->id);
                                })
                                ->orWhere(function($q) use ($authId, $connectedUser) {
                                    $q->where('sender_id', $connectedUser->id)
                                      ->where('receiver_id', $authId);
                                })
                                ->orderBy('created_at', 'desc')
                                ->first();
    
            $unreadCount = chatMessage::where('sender_id', $connectedUser->id)
                                    ->where('receiver_id', $authId)
                                    // ->where('is_read', 0) // uncomment if you track read/unread
                                    ->count();
    
            return [
                'connected_user' => $connectedUser->id,
                'name' => $connectedUser->name,
                'lastMessage' => $lastMessage ? $lastMessage->message : null,
                'timestamp' => $lastMessage ? $lastMessage->created_at->format('g:i A') : null,
                'avatar' => $avatar,
                'unreadCount' => $unreadCount,
                'isOnline' => $connectedUser->status === 'active',
            ];
        });
    
        return response()->json([
            'status' => true,
            'data' => $connectedUsers,
            'pagination' => [
                'total' => $userConnections->total(),
                'per_page' => $userConnections->perPage(),
                'current_page' => $userConnections->currentPage(),
                'last_page' => $userConnections->lastPage(),
            ],
        ]);
    }
    
    
    public function connectedUsers(Request $request)
    {
        $user = Auth::user();
        $authId = $user->id;
    
        $perPage = $request->get('per_page', 10); // default 10 per page
    
        // Get all connections first
        $userConnections = UserConnection::where('sender_id', $authId)
            ->orWhere('receiver_id', $authId)
            ->with(['sender', 'receiver'])
            ->get(); // get all, we'll handle pagination later
    
        if ($userConnections->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No connections yet.',
            ], 200);
        }
    
        $connectedUsers = $userConnections->map(function ($connection) use ($authId) {
            $connectedUser = $connection->sender_id == $authId ? $connection->receiver : $connection->sender;
            $userDetail = $connectedUser->detail;
    
            $avatar = $userDetail && $userDetail->profile_image 
                ? url('storage/' . $userDetail->profile_image) 
                : "";
    
            $lastMessage = chatMessage::where(function($q) use ($authId, $connectedUser) {
                                    $q->where('sender_id', $authId)
                                      ->where('receiver_id', $connectedUser->id);
                                })
                                ->orWhere(function($q) use ($authId, $connectedUser) {
                                    $q->where('sender_id', $connectedUser->id)
                                      ->where('receiver_id', $authId);
                                })
                                ->orderBy('created_at', 'desc')
                                ->first();
    
            $unreadCount = chatMessage::where('sender_id', $connectedUser->id)
                                    ->where('receiver_id', $authId)
                                    // ->where('is_read', 0) // uncomment if tracking read/unread
                                    ->count();
    
            return [
                'connected_user' => $connectedUser->id,
                'name' => $connectedUser->name,
                'lastMessage' => $lastMessage ? $lastMessage->message : null,
                'timestamp' => $lastMessage ? $lastMessage->created_at : null, // keep as Carbon for sorting
                'avatar' => $avatar,
                'unreadCount' => $unreadCount,
                'isOnline' => $connectedUser->status === 'active',
            ];
        });
    
        // Sort by last message timestamp descending, nulls last
        $connectedUsers = $connectedUsers->sortByDesc(function($user) {
            return $user['timestamp'] ? $user['timestamp']->timestamp : 0;
        })->values();
    
        // Paginate manually
        $page = $request->get('page', 1);
        $paginated = $connectedUsers->forPage($page, $perPage);
    
        return response()->json([
            'status' => true,
            'data' => $paginated,
            'pagination' => [
                'total' => $connectedUsers->count(),
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil($connectedUsers->count() / $perPage),
            ],
        ]);
    }
    */


}

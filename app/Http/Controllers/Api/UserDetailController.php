<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\QRCode;
use App\Models\UserConnection;
use App\Models\RecentActivity;
use App\Models\IDVerificationDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

use Illuminate\Support\Facades\Crypt;
use SimpleSoftwareIO\QrCode\Facades\QrCode as QrCodeGenerator;

use Illuminate\Support\Facades\Http;


class UserDetailController extends Controller
{
    public function show_old(Request $request)
    {
        $detail = $request->user()->detail;
        if ($detail && $detail->profile_image) {
            $detail->profile_image_url = url('storage/' . $detail->profile_image);
        } else {
            $detail->profile_image_url = ""; // fallback if no image
        }
        return response()->json([
            'success' => true,
            'user' => $request->user(),
        ]);
    }
    
    public function show(Request $request)
    {
        $userId = $request->user()->id;
    
        // Cache for 5 minutes (adjust as needed)
        $response = Cache::remember("user_detail_{$userId}", now()->addMinutes(10), function () use ($request,$userId) {
            $user = $request->user();
            $detail = $user->detail;
    
            if ($detail) {
                $detail->profile_image_url = $detail->profile_image
                    ? url('storage/' . $detail->profile_image)
                    : "";
            }
            
            $QRCode = QRCode::where('user_id',$userId)->first();
            $qrcodePath = "";
            if($QRCode){
               $qrcodePath = $QRCode->path
                    ? url('storage/qrcodes/' . $QRCode->path)
                    : "";
            }
            
            unset(
                $user->created_at,
                $user->updated_at,
                $detail->created_at,
                $detail->updated_at,
                $detail->subscription_id,
                $detail->stripe_price_id,
                $detail->stripe_customer_id
            );
            // Include detail in user array if needed
            $userArray = $user->toArray();
            $userArray['detail'] = $detail;
            $userArray['detail']['qrcode_path'] = $qrcodePath; 
            
            
            //unset($userArray['detail']['user_id']);
            unset($userArray['detail']['id']);
            
            return [
                'success' => true,
                'message' => 'details fetched successfully',
                'user' => $userArray
            ];
        });
    
        return response()->json($response);
    }
    
    public function getUserDetails($id)
    {
        $user = User::find($id);
        //dd($user);
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 404);
        }
        $detail = $user->detail;
        
        $authUser = Auth::user();
        $existingConnection = connectionExists($authUser->id, $user->id);
        //dd($existingConnection);
        if ($detail) {
            $detail->profile_image_url = $detail->profile_image
                ? url('storage/' . $detail->profile_image)
                : "";
        }
        //var_dump($existingConnection);
        if(!$existingConnection || $existingConnection->status != 'accepted'){
           return response()->json([
                'status'=>false,
                'message' => 'you are not connect to this user.'
            ], 403);
        }
        
        unset(
            $user->created_at,
            $user->updated_at,
            $detail->id,
            $detail->user_id,
            $detail->created_at,
            $detail->updated_at,
            $detail->subscription_id,
            $detail->stripe_price_id,
            $detail->stripe_customer_id
        );

        // Include detail in user array if needed
        $userArray = $user->toArray();
        $userArray['detail'] = $detail;
        return response()->json([
                'status'=>true,
                'user' => $userArray
            ], 200);
        
    }

    public function create(Request $request)
    {
        
        if ($request->user()->detail) {
            return response()->json([
                'message' => 'User details already exist. Use update instead.'
            ], 400);
        }

        $validated = $this->validateData($request);

        if ($request->hasFile('profile_image')) {
            $validated['profile_image'] = $this->storeImage($request);
        }

        $detail = UserDetail::create(array_merge($validated, [
            'user_id' => $request->user()->id,
        ]));

        return response()->json([
            'message' => 'User details created successfully.',
            'data' => $detail,
        ], 201);
    }

public function update(Request $request)
{
    $genderOptions = ['male', 'female'];
    $genderIdentityOptions = ['male', 'female', 'trans-male', 'trans-female', 'non-binary', 'genderqueer', 'genderfluid', 'agender', 'other'];
    $genderOrientationOptions = ['heterosexual', 'gay', 'lesbian', 'bisexual', 'pansexual', 'asexual', 'queer', 'demisexual', 'questioning', 'other'];
    $pronounOptions = ['he/him', 'she/her', 'they/them', 'he/they', 'she/they', 'other'];

    // Base validation rules
    $rules = [
        'user_name'     => ['required','string','max:255',Rule::unique('user_details', 'user_name')->ignore($user->id, 'user_id')],
        'name'          => 'nullable|string|max:255',
        'phone'         => 'nullable|string|max:20',
        'address'       => 'nullable|string|max:255',
        'dob'           => 'nullable|date',
        'gender'              => ['nullable', Rule::in($genderOptions)],
        'gender_identity'     => ['nullable', Rule::in($genderIdentityOptions)],
        'gender_orientation'  => ['nullable', Rule::in($genderOrientationOptions)],
        'pronouns'            => ['nullable', Rule::in($pronounOptions)],
        'bio'           => 'nullable|string|max:500',
        'profile_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        // "Other" fields (conditionally required)
        'gender_other'             => 'nullable|string|max:100',
        'gender_identity_other'    => 'nullable|string|max:100',
        'gender_orientation_other' => 'nullable|string|max:100',
        'pronouns_other'           => 'nullable|string|max:100',
    ];


    // Conditional validation: require "other" text if selected
    if ($request->gender === 'other') {
        $rules['gender_other'] = 'required|string|max:100';
    }
    if ($request->gender_identity === 'other') {
        $rules['gender_identity_other'] = 'required|string|max:100';
    }
    if ($request->gender_orientation === 'other') {
        $rules['gender_orientation_other'] = 'required|string|max:100';
    }
    if ($request->pronouns === 'other') {
        $rules['pronouns_other'] = 'required|string|max:100';
    }
    // Apply validation
    $validated = $request->validate($rules);

    // Replace "other" with custom text
    $validated['gender'] = $request->gender === 'other' ? $request->gender_other : $request->gender;
    $validated['gender_identity'] = $request->gender_identity === 'other' ? $request->gender_identity_other : $request->gender_identity;
    $validated['gender_orientation'] = $request->gender_orientation === 'other' ? $request->gender_orientation_other : $request->gender_orientation;
    $validated['pronouns'] = $request->pronouns === 'other' ? $request->pronouns_other : $request->pronouns;


    $user = $request->user();
    $detail = $user->detail;
    
    //dd($request->all);

    // Check if username is already taken
    /*
    $user_name_taken = UserDetail::where('user_name', $request->user_name)
        ->where('user_id', '!=', $user->id)
        ->exists();

    if ($user_name_taken) {
        return response()->json([
            'success' => false,
            'message' => 'Username already taken.',
        ], 422);
    }
    */

    // Fields allowed to update anytime
    $alwaysUpdatable = ['profile_image', 'bio', 'phone'];

    // Fields that can be updated only once
    $restrictedFields = ['user_name', 'address', 'dob', 'gender','gender_orientation', 'pronouns'];

    // Restrict updating fields that already have values
    if ($detail) {
        foreach ($restrictedFields as $field) {
            if (!empty($detail->$field) && $request->filled($field) && $request->$field !== $detail->$field) {
                $message = "You can only set '$field' once and it cannot be changed again.";
                if($field == 'user_name'){
                    $message = 'user_name can not be updated, it gets generated automatically.';
                }
                return response()->json([
                    'success' => false,
                    'message' =>  $message,
                ], 403);
            }
        }
    }

    // Handle 'name' separately (since it's in users table)
    if (!empty($user->name) && $request->filled('name') && $request->name !== $user->name) {
        return response()->json([
            'success' => false,
            'message' => "You can only set your name once and it cannot be changed again.",
        ], 403);
    }

    // Handle profile image upload
    if ($request->hasFile('profile_image')) {
        $path = $request->file('profile_image')->store('profile_images', 'public');

        // Delete old image if exists
        if ($detail && $detail->profile_image && Storage::disk('public')->exists($detail->profile_image)) {
            Storage::disk('public')->delete($detail->profile_image);
        }

        $validated['profile_image'] = $path;
    } else {
        unset($validated['profile_image']);
    }

    // Create or update user_details
    if (!$detail) {
        $detail = UserDetail::create(array_merge($validated, [
            'user_id' => $user->id,
        ]));
    } else {
        $detail->update($validated);
    }

    // Update user's name (only if not already set)
    if (empty($user->name) && !empty($validated['name'])) {
        $user->name = $validated['name'];
        $user->save();
    }

    Cache::forget("user_detail_{$user->id}");

    $profileImageUrl = $detail->profile_image ? url('storage/' . $detail->profile_image) : null;
    $user['detail']['profile_image_url'] = $profileImageUrl;

    RecentActivity::create([
        'user_id' => $user->id,
        'action'  => 'Update',
        'details' => 'Profile details updated',
        'type'    => 'profile',
    ]);

    unset(
        $user->created_at,
        $user->updated_at,
        $user->detail->created_at,
        $user->detail->updated_at,
        $user->detail->subscription_id,
        $user->detail->stripe_price_id,
        $user->detail->stripe_customer_id
    );

    return response()->json([
        'success' => true,
        'message' => 'User details update successfully.',
        'data'  => $user,
    ], 200);
}

    
    public function GenerateQRcode()
    {
        $user = Auth::user()->load('detail');
        //dd($user);
        
        // Prepare user details
        $data =[
            'id'       => $user->id,
            'name'     => $user->name,
            'email'    => $user->email,
            'status'   => $user->status,
            'details'  => [
                'user_name'   => $user->detail->user_name,
                'phone'       => $user->detail->phone,
                'dob'         => $user->detail->dob,
                'gender'      => $user->detail->gender,
                'pronouns'    => $user->detail->pronouns,
                'bio'         => $user->detail->bio,
                'profile_image' => asset('storage/'.$user->detail->profile_image),
            ]
        ];
        $encryptedData = Crypt::encryptString(json_encode($data));
        $compressed = base64_encode(gzcompress($encryptedData, 9));
        $logoPath = asset('storage/website/logo.jfif');
        //dd( $logoPath);
        
        $qrString = json_encode($data);
        $qr = QrCodeGenerator::size(300)
                //->style('square')
                ->style('dot')
                //->eye('square')
                ->eye('circle')
                ->eyeColor(0, 60, 29, 113, 60, 29, 113)
                ->eyeColor(1, 60, 29, 113, 60, 29, 113)
                ->eyeColor(2, 60, 29, 113, 60, 29, 113)
                ->color(0, 0, 0)
                ->margin(1)
                ->merge($logoPath, .25, true)
                ->format('png')
                ->generate($qrString);
        // Save as png
        //$fileName = 'user_' . $user->id . '_qr.png';
        //Storage::disk('public')->put('qrcodes/' . $fileName, $qr);
        
        $fileName = 'user_' . $user->id . '_qr.png';
        $filePath = 'qrcodes/' . $fileName;
        Storage::disk('public')->put($filePath, $qr);
        
        // Check if QR record exists
        $qrRecord = QRCode::where('user_id', $user->id)->first();
        if($qrRecord && $qrRecord->generated_count>=5){
            return response()->json([
                'status' => false,
                'message' => 'QR code can be generated 5 times only.'
            ],403);
        }
        if ($qrRecord) {
            // Increment generated count
            $qrRecord->increment('generated_count');
            $qrRecord->update([
                'qr_data' => $data,
                'type' => 'user_info',
                'scanned_at' => null,
            ]);
        } else {
            // Create new record
            $qrRecord = QRCode::create([
                'user_id' => $user->id,
                'qr_data' => $data,
                'generated_count' => 1,
                'type' => 'user_info',
                'path' =>$fileName,
                'scanned_at' => null,
            ]);
        }
        Cache::forget("user_detail_{$user->id}");
        return response()->json([
            'status' => true,
            'message' => 'QR code created successfully.',
            'file' => asset('storage/qrcodes/' . $fileName)
        ]);
    }
    
    
    /// RecentActivity
    public function RecentActivity(Request $request)
    {
        $user = $request->user();

        $activities = RecentActivity::where('user_id', $user->id)
            ->latest() // order by created_at descending
            ->take(10)
            ->get();

        return response()->json([
            'status' => true,
            'data' => $activities,
        ]);
    }
    
    ///
    private function generateSignature($method, $path, $timestamp, $body = '')
    {
        $appToken = env('SUMSUB_APP_TOKEN');
        $secretKey = env('SUMSUB_SECRET_KEY');
        
        // Create the signature string
        $signatureString = $timestamp . strtoupper($method) . $path . $body;
        
        // Generate HMAC signature
        $signature = hash_hmac('sha256', $signatureString, $secretKey);
        
        return $signature;
    }
    
    public function createApplicant(Request $request)
    {
        
        $user = $request->user();
        $userDetails = $user->detail;
        $levelName = env('SUMSUB_LEVEL_NAME');
        // Prepare request data
        $path = "/resources/applicants?levelName={$levelName}";
        $method = 'POST';
        $timestamp = time();
        //dd($request->all(),$user, $user->detail);
        $body = json_encode([
            'externalUserId' => (string)$user->id,
            'fixedInfo' => [
                // "firstName"=> $request->firstName,
                // "lastName"=> $request->lastName,
                // "fullName" => $request->firstName . ' ' . $request->lastName,
                "dob"=> $userDetails->dob,
            ],
            'email' => $user->email ?? 'no-email@example.com',
            'phone' => $userDetails->phone ?? null, // optional
            "registrationDate"=>date('Y-m-d H:i:s'),
            "type"=>"individual"
        ]);

        
        // Generate signature
        $signature = $this->generateSignature($method, $path, $timestamp, $body);
        
        // Make the request
        $response = Http::withHeaders([
            'X-App-Token' => env('SUMSUB_APP_TOKEN'),
            'X-App-Access-Ts' => $timestamp,
            'X-App-Access-Sig' => $signature,
            'Content-Type' => 'application/json',
        ])->post(env('SUMSUB_BASE_URL') . $path, json_decode($body, true));
        
        return response()->json($response->json());
    }
    
    public function getApplicantStatus(Request $request, $applicantId)
    {
        $path = "/resources/applicants/{$applicantId}/status";
        $method = 'GET';
        $timestamp = time();
        
        $signature = $this->generateSignature($method, $path, $timestamp);
        
        $response = Http::withHeaders([
            'X-App-Token' => env('SUMSUB_APP_TOKEN'),
            'X-App-Access-Ts' => $timestamp,
            'X-App-Access-Sig' => $signature,
        ])->get(env('SUMSUB_BASE_URL') . $path);
        
        return response()->json($response->json());
    }
    
    public function generateAccessToken(Request $request)
    {
        $user = $request->user();
    
        // Use the SDK endpoint and include TTL and externalActionId in body
        $path = "/resources/accessTokens/sdk";
        $method = 'POST';
        $timestamp = time();
        
        $externalUserId = (string)$user->id;
        $body = json_encode([
            'ttlInSecs' => 600,
            'userId' => $externalUserId,
            'levelName' => env('SUMSUB_LEVEL_NAME'),
        ]);
        /*$body = json_encode([
            'ttlInSecs' => 600, // token valid for 10 minutes
            'userId' => (string)$user->id, // Sumsub applicant ID
            'levelName' => env('SUMSUB_LEVEL_NAME'),
            // 'externalActionId' => (string)$user->id . '-1', // optional external ID
        ]);*/
    
        $signature = $this->generateSignature($method, $path, $timestamp, $body);
    
        $response = Http::withHeaders([
            'X-App-Token' => env('SUMSUB_APP_TOKEN'),
            'X-App-Access-Ts' => $timestamp,
            'X-App-Access-Sig' => $signature,
            'Content-Type' => 'application/json',
        ])->withBody($body, 'application/json')
          ->post(env('SUMSUB_BASE_URL') . $path);
    
        return response()->json($response->json());
    }
    
    public function getApplicantInfo($applicantId)
    {
        try {
            //$path = "/resources/applicants/{$applicantId}/one";
            $path = "/resources/applicants/{$applicantId}";
            $method = 'GET';
            $timestamp = time();
            $signature = $this->generateSignature($method, $path, $timestamp);
    
            $response = Http::withHeaders([
                'X-App-Token' => env('SUMSUB_APP_TOKEN'),
                'X-App-Access-Ts' => $timestamp,
                'X-App-Access-Sig' => $signature,
            ])->get(env('SUMSUB_BASE_URL') . $path);
    
            // Throw an exception if status code is not 2xx
            $response->throw();
    
            return response()->json([
                'success' => true,
                'data' => $response->json(),
            ]);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            // Handle HTTP client exceptions (4xx, 5xx)
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch applicant info',
                'error' => $e->response ? $e->response->json() : $e->getMessage(),
            ], $e->response ? $e->response->status() : 500);
        } catch (\Exception $e) {
            // Handle any other exceptions
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    //
    public function showVerificationPage()
    {
        return view('verification.index');
    }
    public function generateAccessTokenWeb(Request $request)
    {
        $user = $request->user();
    
        $path = "/resources/accessTokens/sdk";
        $method = 'POST';
        $timestamp = time();
        $externalUserId = (string)$user->id;
        $body = json_encode([
            'ttlInSecs' => 600,
            'userId' => $externalUserId,
            'levelName' => env('SUMSUB_LEVEL_NAME'),
        ]);
    
        $signature = $this->generateSignature($method, $path, $timestamp, $body);
    
        $response = Http::withHeaders([
            'X-App-Token' => env('SUMSUB_APP_TOKEN'),
            'X-App-Access-Ts' => $timestamp,
            'X-App-Access-Sig' => $signature,
            'Content-Type' => 'application/json',
        ])->withBody($body, 'application/json')
          ->post(env('SUMSUB_BASE_URL') . $path);
    
        $json = $response->json();
    
        return response()->json([
            'token' => $json['token'] ?? null,
            'raw' => $json, // optional for debugging
        ]);
    }


    public function handleWebhook(Request $request)
    {
        $body = $request->getContent();
        $algoHeader = $request->header('x-payload-digest-alg');
        $digestHeader = $request->header('x-payload-digest');
    
        // Map the algorithm from Sumsub header to PHP hash algo
        $algo = match ($algoHeader) {
            'HMAC_SHA1_HEX' => 'sha1',
            'HMAC_SHA256_HEX' => 'sha256',
            'HMAC_SHA512_HEX' => 'sha512',
            default => null,
        };
    
        if (!$algo) {
            Log::warning('Sumsub webhook: Unsupported algorithm', ['algo' => $algoHeader]);
            return response()->json(['error' => 'Unsupported algorithm'], 400);
        }
    
        // Compute the signature (hex, not base64)
        $computed = hash_hmac(
            $algo,
            $body,
            env('SUMSUB_WEBHOOK_SECRET')
        );
    
        if (!hash_equals($digestHeader, $computed)) {
            Log::warning('Invalid Sumsub webhook signature', [
                'received' => $digestHeader,
                'computed' => $computed,
                'algo' => $algoHeader,
            ]);
            return response()->json(['error' => 'Invalid signature'], 403);
        }
    
        // Signature is valid — process webhook
        $payload = $request->all();
        Log::info('Valid Sumsub webhook received', $payload);
    
        // Example: handle applicant status
        $applicantId = $payload['applicantId'] ?? null;
        $reviewAnswer = $payload['reviewResult']['reviewAnswer'] ?? null;
        $reviewStatus = $payload['reviewStatus'] ?? null;
        $createdAt = \Carbon\Carbon::parse($payload['createdAt'])->format('Y-m-d H:i:s');
        
        $info = $payload['info'] ?? [];
        $review = $payload['review'] ?? [];
        $reviewResult = $review['reviewResult'] ?? [];
    
        if ($applicantId) {
    
            // Assuming you can get $user from applicantId mapping in your system
            $user = User::where('id', $payload['externalUserId'] ?? 0)->first();
            $userDetails = $user?->details; // adjust if you have related details table
    
            $IdVerification = IDVerificationDetail::updateOrCreate(
                ['applicant_id' => $applicantId],
                [
                    //'user_id' => $user?->id,
                    'user_id' => $payload['externalUserId'],
                    'review_answer' => $reviewAnswer,
                    'review_status' => $reviewStatus,
                ]
            );
            $IdVerification->timestamps = false;   // disable auto timestamps
            $IdVerification->created_at = $createdAt;
            $IdVerification->updated_at = Carbon::now()->format('Y-m-d H:i:s');
            $IdVerification->save();
            
            if($reviewAnswer == 'GREEN' && $reviewStatus == 'completed'){
                $user->id_verified = true;
                $user->save();
            }else{
                $user->id_verified = false;
                $user->save();
            }
            Cache::forget("user_detail_{$user->id}");
            //Log::info('user', $user);
        }
    
        return response()->json(['success' => true]);
    }


}

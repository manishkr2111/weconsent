<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ConsentRequest;
use App\Models\BillingDetail;
use App\Models\UserConnection;
use App\Models\QRCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function dashboard()
    {
        $totalUsers = User::count();
        $userStatusCounts = User::select('status', \DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status'); // returns ['pending' => 10, 'accepted' => 5, ...]

        $totalSubscriptions = BillingDetail::count();

        $activeSubscriptions = User::whereHas('detail', function ($query) {
            $query->where('subscription_status', 'active');
        })->count();

        $totalConsentRequests = ConsentRequest::count();
        $consentStatusCounts = ConsentRequest::select('status', \DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status'); // returns ['pending' => 10, 'accepted' => 5, ...]
        //dd($consentStatusCounts);

        // Latest records
        $latestUsers = User::orderBy('created_at', 'desc')->limit(5)->get();
        $latestSubscriptions = BillingDetail::orderBy('created_at', 'desc')->limit(5)->get();
        $latestConsentRequests = ConsentRequest::orderBy('created_at', 'desc')->limit(5)->get();

        return view('admin.dashboard', compact(
            'totalUsers',
            'userStatusCounts',
            'totalSubscriptions',
            'activeSubscriptions',
            'totalConsentRequests',
            'consentStatusCounts',
            'latestUsers',
            'latestSubscriptions',
            'latestConsentRequests'
        ));
    }

    public function consentRequests(Request $request)
    {
        $search = $request->input('search');

        $consent_requests = ConsentRequest::query()
            ->when($search, function ($query, $search) {
                $query->whereHas('createdBy', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })->orWhereHas('sentTo', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(50)
            ->withQueryString(); // keeps search in pagination links

        return view('admin.consent.consent-requests', compact('consent_requests', 'search'));
    }

    public function editOrUpdate(Request $request, ConsentRequest $consentRequest)
    {
        if ($request->isMethod('put')) {
            $request->validate([
                'status' => 'required|in:pending,accepted,rejected,cancelled,expired',
            ]);

            if ($consentRequest->consent_type == 'connection') {
                $userConnection = UserConnection::where('consent_id', $consentRequest->id)->first();
                //dd($userConnection);
                if ($userConnection) {
                    $userConnection->status = $request->status;
                    $userConnection->save();
                }
            }
            $consentRequest->update(['status' => $request->status]);

            return back()->with('success', 'Consent request status updated successfully.');
        }

        return view('admin.consent.editUpdate', compact('consentRequest'));
    }


    public function subscriptionList(Request $request)
    {
        $search = $request->input('search');

        $subscriptions = BillingDetail::with('user') // eager load user
            ->when($search, function ($query, $search) {
                $query->where('subscription_id', 'like', "%{$search}%")
                    ->orWhere('customer_id', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(5)
            ->withQueryString();

        return view('admin.subscriptions.index', compact('subscriptions', 'search'));
    }

    public function QrCodes()
    {
        // Get all QR codes with user info
        $qrcodes = QRCode::with('user')->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.qrcodes.index', compact('qrcodes'));
    }

    public function profile()
    {
        $user = Auth::user();
        return view('admin.profile', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        // Validation rules for User
        $userRules = [
            'name'   => 'nullable|string|max:255',
            'status' => 'required|in:active,blocked',
            'email'  => 'nullable|email|max:255|unique:users,email,' . $user->id,
            'email_verified' => 'required|in:0,1',
            'id_verified'    => 'required|in:0,1',
        ];

        // Validation rules for UserDetail
        $genderOptions = ['male', 'female'];
        $genderIdentityOptions = ['male', 'female', 'trans-male', 'trans-female', 'non-binary', 'genderqueer', 'genderfluid', 'agender', 'other'];
        $genderOrientationOptions = ['heterosexual/straight', 'gay', 'lesbian', 'bisexual', 'pansexual', 'asexual', 'queer', 'demisexual', 'questioning', 'other'];
        $pronounOptions = ['he/him', 'she/her', 'they/them', 'he/they', 'she/they', 'other'];

        $detailRules = [
            'user_name'             => ['required', 'string', 'max:255', Rule::unique('user_details', 'user_name')->ignore($user->id, 'user_id')],
            'phone'                 => 'nullable|string|max:20',
            'address'               => 'nullable|string|max:255',
            'dob'                   => 'nullable|date',
            'gender'                => ['nullable', Rule::in($genderOptions)],
            'gender_identity'       => ['nullable', Rule::in($genderIdentityOptions)],
            'gender_orientation'    => ['nullable', Rule::in($genderOrientationOptions)],
            'pronouns'              => ['nullable', Rule::in($pronounOptions)],
            'bio'                   => 'nullable|string|max:500',
            'subscription_status'   => 'nullable|string|max:50',
            'subscription_start_date' => 'nullable|date',
            'subscription_end_date'   => 'nullable|date',
            'profile_image'         => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'gender_other'             => 'nullable|string|max:100',
            'gender_identity_other'    => 'nullable|string|max:100',
            'gender_orientation_other' => 'nullable|string|max:100',
            'pronouns_other'           => 'nullable|string|max:100',
        ];

        // Conditional validation for "other"
        if ($request->gender === 'other') {
            $detailRules['gender_other'] = 'required|string|max:100';
        }
        if ($request->gender_identity === 'other') {
            $detailRules['gender_identity_other'] = 'required|string|max:100';
        }
        if ($request->gender_orientation === 'other') {
            $detailRules['gender_orientation_other'] = 'required|string|max:100';
        }
        if ($request->pronouns === 'other') {
            $detailRules['pronouns_other'] = 'required|string|max:100';
        }

        $validatedUser = $request->validate($userRules);
        $validatedDetail = $request->validate($detailRules);

        // Replace "other" with custom text
        $validatedDetail['gender'] = $request->gender === 'other' ? $request->gender_other : $request->gender;
        $validatedDetail['gender_identity'] = $request->gender_identity === 'other' ? $request->gender_identity_other : $request->gender_identity;
        $validatedDetail['gender_orientation'] = $request->gender_orientation === 'other' ? $request->gender_orientation_other : $request->gender_orientation;
        $validatedDetail['pronouns'] = $request->pronouns === 'other' ? $request->pronouns_other : $request->pronouns;

        // Handle profile image upload
        if ($request->hasFile('profile_image')) {
            $imagePath = $request->file('profile_image')->store('profile_images', 'public');
            $validatedDetail['profile_image'] = $imagePath;
        }

        $user->name   = $validatedUser['name'];
        $user->email  = $validatedUser['email'];
        $user->status = $validatedUser['status'];
        $user->email_verified_at = $validatedUser['email_verified'] == 1 ? now() : null;
        $user->id_verified = $validatedUser['id_verified'];
        $user->save();

        // Update or create UserDetail
        $user->detail()->updateOrCreate(
            ['user_id' => $user->id],
            $validatedDetail
        );
        return redirect()->back()->with('success', 'User updated successfully.');
        return redirect()->route('users.editOrUpdate', $user)
            ->with('success', 'User updated successfully.');
    }
}

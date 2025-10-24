<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $authUser = Auth::User();
        $search = $request->input('search');

        $users = User::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->where('id', '!=', $authUser->id)
            ->paginate(10); // 10 users per page

        return view('admin.users.index', compact('users', 'search'));
    }
    public function blockerdUsers(Request $request)
    {
        $search = $request->input('search');

        $users = User::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->where('status', 'blocked')
            ->orderBy('created_at', 'desc')
            ->paginate(50); // 10 users per page

        return view('admin.users.index', compact('users', 'search'));
    }
    public function editOrUpdate(Request $request, User $user)
    {
        $user->load('detail');

        if ($request->isMethod('get')) {
            return view('admin.users.edit', compact('user'));
        }

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

    public function activeSubscriptionList()
    {
        $users = User::with('detail')
            ->whereHas('detail', function ($query) {
                $query->where('subscription_status', 'active');
            })
            ->paginate(25);

        return view('admin.users.subscriptions', compact('users'));
    }



    public function create()
    {
        return view('admin.users.create');  // View to create a user
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        User::create($validated);  // Create new user
        return redirect()->route('admin.users.index');
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));  // Edit user view
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $user->update($validated);  // Update the user
        return redirect()->route('admin.users.index');
    }

    public function destroy(User $user)
    {
        $user->delete();  // Delete the user
        return redirect()->route('admin.users.index');
    }
}

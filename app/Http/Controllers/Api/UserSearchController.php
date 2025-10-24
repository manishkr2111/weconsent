<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserDetail;
use App\Models\UserConnection;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class UserSearchController extends Controller
{
    /**
     * Search users by filters
     */
    public function search(Request $request)
    {
        if ($request->filled('search') && strlen($request->input('search')) < 2) {
            return response()->json([
                'error' => 'Search term must be at least 2 characters.'
            ], 422);
        }

        $authUser = Auth::user();

        $query = UserDetail::with('user')->where('user_id', '!=', $authUser->id);
        //dd($authUser);
        if ($request->filled('search')) {
            $search = $request->input('search');

            // Remove '@' from start if it exists
            if (str_starts_with($search, '@')) {
                $search = substr($search, 1);
            }

            $query->where(function ($q) use ($search, $authUser) {
                $q->where('user_name', 'like', "%{$search}%")
                    ->orWhere('bio', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->boolean('verified_only')) {
            $query->whereHas('user', function ($q) {
                $q->whereNotNull('email_verified_at');
            });
        }

        if ($request->filled('gender') && strtolower($request->gender) !== 'any') {
            $query->where('gender', $request->gender);
        }

        if ($request->filled('age_from') && $request->filled('age_to')) {
            $from = now()->subYears($request->age_to)->toDateString();
            $to   = now()->subYears($request->age_from)->toDateString();
            $query->whereBetween('dob', [$from, $to]);
        }

        $users = $query->paginate(10);

        // Attach connection status
        $users->getCollection()->transform(function ($userDetail) use ($authUser) {

            $existingConnection = connectionExists($authUser->id, $userDetail->user_id);
            if ($existingConnection) {
                $userDetail->connected = $existingConnection->status;

                if ($existingConnection->status === 'accepted') {
                    $userDetail->profile_image_url = $userDetail->profile_image
                        ? url('storage/' . $userDetail->profile_image)
                        : "";
                } else {
                    $userDetail['user']['name'] = '';
                    $userDetail['user']['email'] = '';
                    $userDetail['phone'] = '';
                    $userDetail['dob'] = '';
                    $userDetail['bio'] = '';
                    $userDetail['profile_image'] = '';
                    $userDetail['profile_image_url'] = '';
                }
            } else {
                $userDetail->connected = false;
                $userDetail['user']['name'] = '';
                $userDetail['user']['email'] = '';
                $userDetail['phone'] = '';
                $userDetail['dob'] = '';
                $userDetail['bio'] = '';
                $userDetail['profile_image'] = '';
                $userDetail['profile_image_url'] = '';
            }
            return $userDetail;
        });

        return response()->json($users);
    }

    public function search_old(Request $request)
    {
        if ($request->filled('search') && strlen($request->input('search')) < 2) {
            return response()->json([
                'error' => 'Search term must be at least 2 characters.'
            ], 422);
        }
        $authUser = Auth::user();

        // Create a unique cache key based on filters
        $cacheKey = 'search_users_' . md5(json_encode($request->all()));
        Cache::forget($cacheKey);

        $users = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($request, $authUser) {
            $query = UserDetail::with('user');
            //$query->where('user_id', '!=', $authUser->id);
            //dd($query);
            if ($request->filled('search')) {
                $search = $request->input('search');
                $search = $request->input('search');
                // Remove '@' from start if it exists
                if (str_starts_with($search, '@')) {
                    $search = substr($search, 1);
                }
                $query->where(function ($q) use ($search, $authUser) {
                    $q->where('user_id', '!=', $authUser->id)
                        ->where('user_name', 'like', "%{$search}%")
                        ->orWhere('bio', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
                        });
                });
            }

            if ($request->boolean('verified_only')) {
                $query->whereHas('user', function ($q) {
                    $q->whereNotNull('email_verified_at');
                });
            }


            if ($request->filled('gender') && strtolower($request->gender) !== 'any') {
                $query->where('gender', $request->gender);
            }


            if ($request->filled('age_from') && $request->filled('age_to')) {
                $from = now()->subYears($request->age_to)->toDateString();
                $to   = now()->subYears($request->age_from)->toDateString();
                $query->whereBetween('dob', [$from, $to]);
            }

            return $query->paginate(10);
        });
        //dd($users);
        // Attach connection status to each user
        $users->getCollection()->transform(function ($userDetail) use ($request, $authUser) {
            /*$existingConnection = UserConnection::where(function ($query) use ($request, $userDetail,$authUser) {
                    $query->where([
                        ['receiver_id', $userDetail->user_id],
                        ['sender_id', $authUser->id],
                    ])->orWhere([
                        ['receiver_id', $authUser->id],
                        ['sender_id', $userDetail->user_id],
                    ]);
                })->first();*/
            //dd($userDetail,'hello',$request->all());

            $existingConnection1 = UserConnection::where('receiver_id', $authUser->id)->where('sender_id', $userDetail->user_id)->first();
            $existingConnection2 = UserConnection::where('sender_id', $authUser->id)->where('receiver_id', $userDetail->user_id)->first();
            //dd($existingConnection1, 'hello', $existingConnection2);
            if ($existingConnection1) {
                $userDetail->connected = $existingConnection1->status; // "pending", "accepted", "blocked"
            } elseif ($existingConnection2) {
                $userDetail->connected = $existingConnection2->status;
            } else {
                $userDetail->connected = false;
            }
            $userDetail->profile_image_url = $userDetail->profile_image
                ? url('storage/' . $userDetail->profile_image)
                : "";
            return $userDetail;
        });

        return response()->json($users);
    }

    /**
     * Discover random users
     */
    public function discoverRandom(Request $request)
    {
        $users = Cache::remember('discover_random_users', now()->addMinutes(5), function () {
            return UserDetail::with('user')
                ->inRandomOrder()
                ->limit(10)
                ->get();
        });

        return response()->json($users);
    }
}

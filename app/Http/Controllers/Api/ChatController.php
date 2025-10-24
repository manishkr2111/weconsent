<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Events\MessageSent;
use App\Models\chatMessage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\UserConnection;

class ChatController extends Controller
{
    public function sendMessage(Request $request)
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'receiver_id' => 'required|exists:users,id',
                'message' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            }
            $user = Auth::user();
            $authId = $user->id;

            /*
            $existingConnection_old = UserConnection::where(function ($q) use ($request, $authId) {
                $q->where('receiver_id', $request->receiver_id)
                  ->where('sender_id', $authId);
            })->orWhere(function ($q) use ($request, $authId) {
                $q->where('sender_id', $request->receiver_id)
                  ->where('receiver_id', $authId);
            })->first();
            */
            $existingConnection = connectionExists($authId, $request->receiver_id);
            if ($existingConnection && $existingConnection->status == 'accepted') {
                // Create the message
                $message = chatMessage::create([
                    'sender_id' => auth()->id(),
                    'receiver_id' => $request->receiver_id,
                    'message' => $request->message,
                ]);

                // Broadcast the event
                broadcast(new MessageSent($message))->toOthers();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Message sent successfully',
                    'data' => $message
                ], 201);
            } else {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'You are not connected yet, send connection request first.',
                ], 403);
            }
        } catch (\Exception $e) {
            // Handle unexpected errors
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong'
            ], 500);
        }
    }

    public function getMessages(Request $request, $userId)
    {
        $authId = auth()->id();
        //dd('hello');
        $messages = chatMessage::where(function ($q) use ($authId, $userId) {
            $q->where('sender_id', $authId)
                ->where('receiver_id', $userId);
        })
            ->orWhere(function ($q) use ($authId, $userId) {
                $q->where('sender_id', $userId)
                    ->where('receiver_id', $authId);
            })
            ->orderBy('created_at', 'desc') // newest first
            ->paginate($request->get('per_page', 50)); // default 20 per page

        // Add message_type for each message
        $messages->getCollection()->transform(function ($message) use ($authId) {
            $message->message_type = $message->sender_id == $authId ? 'sent' : 'received';
            return $message;
        });

        $unreadMessages = chatMessage::where('sender_id', $userId)
            ->where('receiver_id', $authId)
            ->where('is_read', 'no') // uncomment if tracking read/unread
            ->get();
        // dd($unreadMessages);                            
        foreach ($unreadMessages as $unreadMessage) {
            //dd($unreadMessage);
            $unreadMessage->is_read = 'yes';
            $unreadMessage->save();
        }
        return response()->json($messages);
    }



    public function getConversations()
    {
        //$authId = auth()->id();
        $user = Auth::user();
        $authId = $user->id;
        //dd($authId);
        // Fetch latest message for each user the auth user has chatted with
        $messages = chatMessage::where(function ($q) use ($authId) {
            $q->where('sender_id', $authId)
                ->orWhere('receiver_id', $authId);
        })
            ->with(['sender:id,name', 'receiver:id,name']) // eager load minimal user data
            ->orderBy('created_at', 'desc')
            ->get();

        $conversations = [];

        foreach ($messages as $msg) {
            $chatUserId = $msg->sender_id == $authId ? $msg->receiver_id : $msg->sender_id;

            if (!isset($conversations[$chatUserId])) {
                $conversations[$chatUserId] = [
                    'user_id' => $chatUserId,
                    'user_name' => $msg->sender_id === $authId ? $msg->receiver->name : $msg->sender->name,
                    'last_message' => $msg->message,
                    'last_message_time' => $msg->created_at->toDateTimeString(),
                ];
            }
        }

        return response()->json(array_values($conversations));
    }

    public function getallConversations()
    {
        dd('hello');
    }

    public function connectedUsers(Request $request)
    {
        $user = Auth::user();
        $authId = $user->id;

        $perPage = $request->get('per_page', 10); // default 10 per page

        // Get all connections first
        /*$userConnections = UserConnection::where('sender_id', $authId)
            ->orWhere('receiver_id', $authId)
            ->with(['sender', 'receiver'])
            ->get(); */
        $userConnections = UserConnection::where(function ($q) use ($authId) {
            $q->where('sender_id', $authId)
                ->orWhere('receiver_id', $authId);
        })
            ->where('status', 'accepted')
            ->with(['sender', 'receiver'])
            ->get(); // later you can replace get() with paginate()

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

            $lastMessage = chatMessage::where(function ($q) use ($authId, $connectedUser) {
                $q->where('sender_id', $authId)
                    ->where('receiver_id', $connectedUser->id);
            })
                ->orWhere(function ($q) use ($authId, $connectedUser) {
                    $q->where('sender_id', $connectedUser->id)
                        ->where('receiver_id', $authId);
                })
                ->orderBy('created_at', 'desc')
                ->first();

            $unreadCount = chatMessage::where('sender_id', $connectedUser->id)
                ->where('receiver_id', $authId)
                ->where('is_read', 'no') // uncomment if tracking read/unread
                ->count();
            //dd($unreadCount);

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
        $connectedUsers = $connectedUsers->sortByDesc(function ($user) {
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

    public function readchat(Request $request, $userId)
    {
        // dd('hello');
        $user = Auth::user();
        $existingConnection = UserConnection::where(function ($q) use ($request, $user) {
            $q->where('receiver_id', $userId)
                ->where('sender_id', $user->id);
        })
            ->orWhere(function ($q) use ($request, $user) {
                $q->where('sender_id', $userId)
                    ->where('receiver_id', $user->id);
            })
            ->first();
        dd($existingConnection);

        if ($existingConnection) {
            $unreadMessage = chatMessage::where('sender_id', $request->id)
                ->where('receiver_id', $user->id)
                ->where('is_read', 'no') // uncomment if tracking read/unread
                ->get();
        }
    }
}

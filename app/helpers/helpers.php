<?php

use Illuminate\Support\Facades\Mail;
use App\Mail\ConsentRequestNotification;
use App\Models\UserConnection;

if (!function_exists('sendConsentRequestEmails')) {
    /**
     * Send Consent Request Email to both author and receiver
     *
     * @param object $author
     * @param object $receiver
     * @param string $type
     * @param string $actionType  (created | accepted)
     * @return array
     */
    function sendConsentRequestEmails($author, $receiver, $type, $actionType = 'created')
    {
        $consentRequest = (object)[
            'type' => $type,
            'sender' => $author,
            'receiver' => $receiver,
        ];

        try {
            Mail::to($author->email)
                ->queue(new ConsentRequestNotification($consentRequest, 'author', $actionType));

            Mail::to($receiver->email)
                ->queue(new ConsentRequestNotification($consentRequest, 'receiver', $actionType));

            return [
                'success' => true,
                'message' => 'Consent request emails sent successfully.'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to send consent request emails.',
                'error' => $e->getMessage()
            ];
        }
    }
}


if (!function_exists('connectionExists')) {
    /**
     * Check if a connection exists between two users
     *
     * @param int $userId1
     * @param int $userId2
     * @return UserConnection|null
     */
    function connectionExists(int $userId1, int $userId2)
    {
        return UserConnection::where(function ($query) use ($userId1, $userId2) {
            $query->where('receiver_id', $userId2)
                  ->where('sender_id', $userId1);
        })->orWhere(function ($query) use ($userId1, $userId2) {
            $query->where('sender_id', $userId2)
                  ->where('receiver_id', $userId1);
        })->first();
    }
}
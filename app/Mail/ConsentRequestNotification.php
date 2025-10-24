<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ConsentRequestNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $consentRequest;
    public $recipientType; // 'author' or 'receiver'
    public $actionType; // 'created' or 'accepted'

    public function __construct($consentRequest, $recipientType, $actionType)
    {
        $this->consentRequest = $consentRequest;
        $this->recipientType = $recipientType;
        $this->actionType = $actionType;
    }

    public function envelope(): Envelope
    {
        $type = $this->consentRequest->type;

        // Set subject dynamically
        if ($this->actionType == 'created') {
            $subject = $this->recipientType === 'author'
                ? "You sent a {$type} request"
                : "New {$type} request received";
        } elseif($this->actionType == 'accepted') { // accepted
            $subject = $this->recipientType === 'author'
                ? "Your {$type} request was accepted"
                : "You accepted a {$type} request";
        }elseif($this->actionType == 'cancelled'){
            $subject = $this->recipientType === 'author'
                ? "Your {$type} request was cancelled"
                : "You cancelled a {$type} request";
        }

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.consent_request_notification',
            with: [
                'consentRequest' => $this->consentRequest,
                'recipientType' => $this->recipientType,
                'actionType' => $this->actionType,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

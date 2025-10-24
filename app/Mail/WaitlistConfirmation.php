<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WaitlistConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public string $email;
    public string $logoPath;

    /**
     * Create a new message instance.
     */
    public function __construct(string $email)
    {
        $this->email = $email;
        $this->logoPath = 'https://dev.weconsent.app/storage/website/logo.jfif';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Thank you for joining the WeConsent waitlist!',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.waitlist_confirmation', // Blade view file
            with: [
                'email' => $this->email, // Pass email to the view
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ConsentOTPEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;  // OTP variable to make it dynamic
    public $userName; // Name of the user to address in the email
    public $subject; // Subject of the email

    /**
     * Create a new message instance.
     *
     * @param string $otp
     * @param string $userName
     * @param string $subject
     */
    public function __construct($otp, $userName, $subject = 'Your OTP Code')
    {
        $this->otp = $otp;
        $this->userName = $userName;
        $this->subject = $subject;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject($this->subject)
                    ->view('emails.consent-otp'); // You'll need to create this view file
    }
}

<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public string $purpose,
        public int $minutes
    ) {}

    public function build()
    {
        $subject = $this->purpose === 'reset'
            ? 'Your StreetSkill password reset code'
            : 'Your StreetSkill verification code';

        return $this->subject($subject)
            ->view('emails.otp');
    }
}

<?php

namespace App\Mail;

use App\Models\Trial;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TrialCredentials extends Mailable
{
    use Queueable, SerializesModels;

    public $trial;

    public function __construct(Trial $trial)
    {
        $this->trial = $trial;
    }

    public function build()
    {
        return $this->subject('Your Trial Access Credentials')
                    ->view('emails.trial-credentials');
    }
}

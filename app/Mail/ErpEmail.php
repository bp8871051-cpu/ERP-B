<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ErpEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $mailSubject;
    public $mailBody;

    public function __construct($mailSubject, $mailBody)
    {
        $this->mailSubject = $mailSubject;
        $this->mailBody = $mailBody;
    }

    public function build()
    {
        return $this->subject($this->mailSubject ?: 'New Email from ERP')
                    ->html($this->mailBody ?: ' ');
    }
}

<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReportResponse extends Mailable
{
    use Queueable, SerializesModels;

    public $title;
    public $body;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($body)
    {
        $this->title = 'رد على رسالتكم في موسوعة الراية';
        $this->body = $body;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('رد على رسالتكم في موسوعة الراية')->markdown('emails.alraya.admin.reportresponse');
    }
}

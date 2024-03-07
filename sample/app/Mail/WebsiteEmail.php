<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WebsiteEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $body;
    public $email;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($email, $name, $body)
    {
        $this->email = $email;
        $this->name = $name;
        $this->body = $body;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('رسالة من الموقع')->markdown('emails.alraya.websitemail');
    }
}

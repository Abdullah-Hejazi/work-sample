<?php

namespace App\Repositories\Mail;

use Illuminate\Mail\Transport\Transport;
use Swift_Mime_SimpleMessage;
use Illuminate\Support\Facades\Http;

class TransmailTransport extends Transport
{
    public function __construct()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function send(Swift_Mime_SimpleMessage $message)
    {
        $this->beforeSendPerformed($message);

        $to = $this->getTo($message);

        $headers = [
            'Authorization'     =>      'Zoho-enczapikey ' . env('TRANSMAIL_TOKEN'),
            'Accept'            =>      'application/json',
            'Content-Type'      =>      'application/json'
        ];

        Http::withHeaders($headers)->post('https://api.transmail.com/v1.1/email', $this->payload($message, $to));

        $message->setBcc($message->getBcc());

        $this->sendPerformed($message);

        return $this->numberOfRecipients($message);
    }

    /**
     * Get the HTTP payload for sending the Transmail message.
     *
     * @param  \Swift_Mime_SimpleMessage  $message
     * @param  array  $to
     *
     * @return array
     */
    protected function payload(Swift_Mime_SimpleMessage $message, $to)
    {
        return [
            'bounce_address'        =>      env('TRANSMAIL_BOUNCE_EMAIL'),
            'from'                  =>      [
                'address'           =>      env('TRANSMAIL_EMAIL'),
                'name'              =>      env('TRANSMAIL_NAME')
            ],
            'to'                    =>      $to,
            'reply_to'              =>      [
                [
                    'address'           =>      env('TRANSMAIL_REPLY_TO_EMAIL'),
                    'name'              =>      env('TRANSMAIL_REPLY_TO_NAME')
                ]
            ],
            'subject'               =>      $message->getSubject(),
            'htmlbody'              =>      $message->getBody()
        ];
    }

    /**
     * Get the "to" payload field for the API request.
     *
     * @param  \Swift_Mime_SimpleMessage  $message
     *
     * @return array
     */
    protected function getTo(Swift_Mime_SimpleMessage $message)
    {
        $result = [];

        foreach($this->allContacts($message) as $email => $name) {
            $result[] = [
                'email_address'     =>      [
                    'address'   =>  $email
                ]
            ];
        }
        return $result;
    }

    /**
     * Get all of the contacts for the message.
     *
     * @param  \Swift_Mime_SimpleMessage  $message
     *
     * @return array
     */
    protected function allContacts(Swift_Mime_SimpleMessage $message)
    {
        return array_merge(
            (array) $message->getTo(),
            (array) $message->getCc(),
            (array) $message->getBcc()
        );
    }

    /**
     * Get the message ID from the response.
     *
     * @param  \Psr\Http\Message\ResponseInterface  $response
     *
     * @return string
     */
    protected function getMessageId($response)
    {
        return object_get(
            json_decode($response->getBody()->getContents()),
            'request_id'
        );
    }
}

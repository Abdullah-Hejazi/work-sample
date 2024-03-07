<?php

namespace App\Repositories\EmailValidation;

use Illuminate\Support\Facades\Http;

class EmailVerifierApi implements EmailValidation {
    protected $key;

    public function __construct() {
        $this->key = config('external.emailverifierkey');
    }

    public function verify(string $email) {
        $email_check = Http::get('https://emailverifierapi.com/v2/', [
            'apiKey'    =>      $this->key,
            'email'     =>      $email
        ]);

        if(array_key_exists('status', $email_check->json()) && $email_check->json()['status'] === 'failed'){
            return false;
        }

        return true;
    }
}

<?php

namespace App\Repositories\EmailValidation;

use Illuminate\Support\Facades\Http;

class NeverBounceValidator implements EmailValidation {
    protected $key;

    public function __construct() {
        $this->key = config('external.neverbouncekey');
    }

    public function verify(string $email) {
        $response = Http::get('https://api.neverbounce.com/v4.2/single/check?timeout=6&key=' . $this->key . '&email=' . $email);

        if ($response->json()['status'] === 'success') {
            if ($response->json()['result'] === 'valid') {
                return true;
            }

            return false;
        }

        return true;
    }
}

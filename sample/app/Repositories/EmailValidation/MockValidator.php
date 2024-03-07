<?php

namespace App\Repositories\EmailValidation;

class MockValidator implements EmailValidation {
    public function verify(string $email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}

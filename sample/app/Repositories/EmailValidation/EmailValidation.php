<?php

namespace App\Repositories\EmailValidation;

interface EmailValidation {
    public function verify(string $email);
}

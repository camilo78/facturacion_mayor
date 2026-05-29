<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Rtn implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('El RTN debe ser texto.');
            return;
        }
        $clean = str_replace('-', '', $value);
        if (! preg_match('/^\d{14}$/', $clean)) {
            $fail('El RTN debe tener 14 dígitos (con o sin guiones).');
        }
    }
}

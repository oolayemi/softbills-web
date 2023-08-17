<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Translation\PotentiallyTranslatedString;

class Phone implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param Closure(string): PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $first_3_xters = Str::of($value)->substr(0, 4);
        $pattern = '/^0(?:70[1-68]|8(?:0[235-9]|1[0-8])|9(?:0[1-9]|1[2356]))$/';
        $check = preg_match($pattern, $first_3_xters, $matches);

        if (! $check || Str::length($value) !== 11) {
            $fail('The :attribute must be a valid phone number.');
        }
    }
}

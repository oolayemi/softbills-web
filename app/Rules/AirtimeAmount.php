<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AirtimeAmount implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Check if the value is greater than or equal to 50 and is a multiple of 50
        if (($value >= 50) && ($value % 50 == 0)){
            return true;
        }else{
            return 'The Amount must be a minimum of 50 Naira and a multiple of 50.';
        }
    }
}

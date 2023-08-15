<?php

namespace App\Services\Traits;

use Illuminate\Support\MessageBag;

trait BaseServiceTrait
{
    /**
     * Compose a validation error from the errors message bag
     */
    private function composeValidationError(MessageBag $errors): string
    {
        $errorsCount = count($errors);
        $errorPlural = ($errorsCount - 1) > 1 ? 'errors' : 'error';
        if ($errorsCount > 1) {
            return rtrim($errors->first(), '.').' plus '.$errorsCount - 1 ." other {$errorPlural}.";
        }

        return $errors->first();
    }

    private function transformPhoneNumber(string $phoneNumber, $country = 'NGN'): string
    {
        if (strlen($phoneNumber) == 10) {
            $phone_number = '234'.$phoneNumber;
        } elseif (strlen($phoneNumber) == 11) {
            $phone_number = '234'.substr($phoneNumber, -10);
        } elseif (str_starts_with($phoneNumber, '234')) {
            $phone_number = $phoneNumber;
        } elseif (str_starts_with($phoneNumber, '+234')) {
            $phone_number = '234'.substr($phoneNumber, -10);
        } else {
            $phone_number = $phoneNumber;
        }

        return $phone_number;
    }

    private function composeUssdCode($ussdCode, $code): string
    {
        return sprintf('*%s*000*%s#', $ussdCode, $code);
    }
}

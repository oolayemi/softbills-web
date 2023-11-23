<?php

namespace App\Services\Helpers;

use App\Models\Wallet;

class GeneralHelper
{

    public static function hasEnoughBalance(Wallet $wallet, $amount): bool
    {
        return $wallet->balance > $amount;
    }
}

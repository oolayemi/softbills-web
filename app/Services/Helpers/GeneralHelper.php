<?php

namespace App\Services\Helpers;

use App\Models\Wallet;
use App\Services\Enums\ServiceType;
use Illuminate\Support\Str;

class GeneralHelper
{

    public static function hasEnoughBalance(Wallet $wallet, $amount): bool
    {
        return $wallet->balance > $amount;
    }

    public static function generateReference(string $service_type = null): string
    {
        $service_types = [
            ServiceType::AIRTIME->value => 'AIRT',
            ServiceType::BETTING->value => 'BETG',
            ServiceType::DATA->value => 'DATA',
            ServiceType::BANK_TRANSFER->value => 'TRSF',
            ServiceType::CABLE_TV->value => 'CBLE',
            ServiceType::ELECTRICITY->value => 'ELEC',
            ServiceType::EPIN->value => 'EPIN',
            ServiceType::COMMISSION->value => 'COMM',
            ServiceType::SME_DATA->value => 'SMED',
            ServiceType::JAMB->value => 'JAMB',
        ];
        $leading = 'SFTB';
        $time = substr(time(), -4);
        $str = Str::upper(Str::random(4));
        $service_type = array_key_exists($service_type, $service_types) ? $service_types[$service_type] : 'TRNX';

        return sprintf('%s|%s|%s%s', $leading, $service_type ?? rand(1111, 9999), $time, $str);
    }
}

<?php

namespace App\Services\Enums;
enum ServiceType: string
{
            case AIRTIME = 'airtime';
            case DATA = 'data';
            case EPIN = 'epin';
            case CABLE_TV = 'cable-tv';
            case ELECTRICITY = 'electricity';
            case BANK_TRANSFER = 'bank-transfer';
            case TRANSFER = 'transfer';
            case BETTING = 'betting';
            case SME_DATA = 'sme-data';
            case JAMB = 'jamb';

}

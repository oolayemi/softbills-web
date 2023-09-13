<?php

namespace App\Services\Constants;

class Providers
{
    public static array $cablePackages = [
        [
            "serviceName" => "DSTV",
            "serviceID" => "dstv",
            "convinience_fee" => "100 %",
        ],
        [
            "serviceName" => "GOTV Payment",
            "serviceID" => "gotv",
            "convinience_fee" => "100 %",
        ],
        [
            "serviceName" => "ShowMax",
            "serviceID" => "showmax",
            "convinience_fee" => "100 %",
        ],
        [
            "serviceName" => "Startimes Subscription",
            "serviceID" => "startimes",
            "convinience_fee" => "100 %",
        ]
    ];
}

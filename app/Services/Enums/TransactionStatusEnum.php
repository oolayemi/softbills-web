<?php

namespace App\Services\Enums;

enum TransactionStatusEnum
{
    case SUCCESSFUL;
    case PENDING;
    case FAILED;
}

<?php

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;
use App\Services\Helpers\ApiResponse;
use App\Services\ThirdPartyAPIs\SageCloudServices;
use Illuminate\Http\Request;

class TransferController extends Controller
{
    public function fetchBanks(SageCloudServices $sageCloud) {
        $response = $sageCloud->fetchBanks();

        if (! $response['success']) {
            return ApiResponse::failed("An error occurred while fetching banks list.");
        }

        return ApiResponse::success("Banks fetched successfully", $response['data']);
    }
}

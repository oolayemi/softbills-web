<?php

namespace App\Http\Controllers;

use App\Services\Helpers\ApiResponse;
use App\Services\ThirdPartyAPIs\MonnifyApis;
use App\Services\ThirdPartyAPIs\SageCloudServices;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function getBanks(SageCloudServices $sageCloud) {
        $response = $sageCloud->fetchBanks();

        if (! $response['success']) {
            return ApiResponse::failed("An error occurred while fetching banks list.");
        }

        return ApiResponse::success("Banks fetched successfully", $response['banks']);
    }

    public function validateName(Request $request, SageCloudServices $sageCloud)
    {
        $request->validate([
            'bank_code' => 'required',
            'account_number' => 'required',
        ]);

        $response = $sageCloud->verifyBankDetails($request->all());

        if (! isset($response['success']) || ! $response['success']) {
            return ApiResponse::failed("An error occurred while validating bank details.");
        }

        return ApiResponse::success("Bank details retrieved successfully", ['account_name' => $response['account_name']]);
    }
}

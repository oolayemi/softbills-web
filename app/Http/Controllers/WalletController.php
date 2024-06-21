<?php

namespace App\Http\Controllers;

use App\Services\Helpers\ApiResponse;
use App\Services\ThirdPartyAPIs\MonnifyApis;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function getBanks() {
        $monnify = new MonnifyApis();
        $response = $monnify->getBanks();

        if (isset($response['requestSuccessful']) && $response['requestSuccessful']) {
            return ApiResponse::success("Banks fetched successfully", $response['responseBody']);
        }

        return ApiResponse::failed("An error occurred while fetching banks");
    }

    public function validateName(Request $request)
    {
        $request->validate([
            'bank_code' => 'required',
            'account_number' => 'required',
        ]);

        $data = [
            'accountNumber' => $request->account_number,
            'bankCode' => $request->bank_code,
        ];

        $monnify = new MonnifyApis();
        $response = $monnify->nameEnquiry($data);

        if (isset($response['requestSuccessful']) && $response['requestSuccessful']) {
            return ApiResponse::success("Banks fetched successfully", $response['responseBody']);
        }

        return ApiResponse::failed("An error occurred while validating name");
    }
}

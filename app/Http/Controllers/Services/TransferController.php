<?php

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;
use App\Services\Enums\ServiceType;
use App\Services\Enums\TransactionStatusEnum;
use App\Services\Enums\TransactionTypeEnum;
use App\Services\Helpers\ApiResponse;
use App\Services\Helpers\GeneralHelper;
use App\Services\ThirdPartyAPIs\SageCloudServices;
use Illuminate\Http\Request;

class TransferController extends Controller
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

    public function makeTransfer(Request $request, SageCloudServices $sageCloud)
    {
        $request->validate([
            'bank_code' => 'required',
            'account_number' => 'required',
            'account_name' => 'required',
            'amount' => 'required|decimal:0,2',
            'narration' => 'nullable',
        ]);

        $user = $request->user();
        $wallet = $user->wallet;

        if (!GeneralHelper::hasEnoughBalance($wallet, $request->amount)){
            return ApiResponse::failed("You don't have sufficient balance to continue");
        }

        $payload = [
            'reference' => GeneralHelper::generateReference(ServiceType::TRANSFER->value),
            'bank_code' => $request->bank_code,
            'account_number' => $request->account_number,
            'account_name' => $request->account_name,
            'amount' => $request->amount,
            'narration' => $request->narration,
        ];

        $data = $request->all();

        $response = $sageCloud->transferFunds($payload);
        $amount = $data['amount'];

        if (isset($response['status']) && $response['status'] != 'failed') {
            $user->walletTransactions()->create([
                'wallet_id' => $wallet->id,
                'reference' => $payload['reference'],
                'amount' => $amount,
                'prev_balance' => $wallet->balance,
                'new_balance' => $wallet->balance - $amount,
                'service_type' => ServiceType::TRANSFER->value,
                'transaction_type' => TransactionTypeEnum::debit->name,
                'status' =>$response['status'] == 'pending' ? TransactionStatusEnum::PENDING->name : TransactionStatusEnum::SUCCESSFUL->name,
                'narration' => 'Transfer of ₦'.  $amount . ' to ' . $request->account_name,
            ]);

            $wallet->balance -= $amount;
            $wallet->save();

            return ApiResponse::success("Data bundle purchase request submitted");
        }

        return ApiResponse::failed("Transfer request failed");
    }
}

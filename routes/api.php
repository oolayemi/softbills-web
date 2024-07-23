<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\NokInformationController;
use App\Http\Controllers\Services\AirtimeController;
use App\Http\Controllers\Services\BettingController;
use App\Http\Controllers\Services\CableTvController;
use App\Http\Controllers\Services\DataController;
use App\Http\Controllers\Services\ElectricityController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WalletController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::prefix('register')->group(function () {
            Route::post('phone/validate', [RegisterController::class, 'validatePhone']);
            Route::post('phone/verify', [RegisterController::class, 'verifyPhoneOtp']);
            Route::post('email/validate', [RegisterController::class, 'validateEmail']);
            Route::post('email/verify', [RegisterController::class, 'verifyEmailOtp']);
            Route::post('', RegisterController::class);
        });
        Route::post('login', LoginController::class);
    });

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::prefix('wallet')->group(function () {
            Route::get('bank-list', [WalletController::class, 'getBanks']);
            Route::post('name-enquiry', [WalletController::class, 'validateName']);

        });


        Route::prefix('user')->group(function () {
            Route::get('profile', [UserController::class, 'userProfile']);
            Route::post('profile/update', [UserController::class, 'editProfile']);
            Route::get('wallet', [UserController::class, 'fetchWalletDetails']);
            Route::get('wallet-transactions', [UserController::class, 'userWalletTransactions']);
            Route::post('bvn/update', [UserController::class, 'tier2Upgrade']);

            Route::prefix('nok')->group(function () {
                Route::get('', [NokInformationController::class, 'index']);
                Route::post('add', [NokInformationController::class, 'store']);
            });

            Route::post('transaction-pin/change', [UserController::class, 'changePin']);
            Route::post('password/change', [UserController::class, 'changePassword']);
        });

        Route::prefix('cable')->group(function () {
            Route::get('providers', [CableTvController::class, 'providers']);
            Route::get('{type}/provider', [CableTvController::class, 'fetchPackages']);
            Route::post('validate', [CableTvController::class, 'validateSmartCard']);
            Route::post('purchase', [CableTvController::class, 'purchase']);
        });

        Route::prefix('betting')->group(function () {
            Route::get('fetch-billers', [BettingController::class, 'providers']);
            Route::post('validate', [BettingController::class, 'validateBetting']);
            Route::post('purchase', [BettingController::class, 'purchase']);
        });

        Route::prefix('electricity')->group(function () {
            Route::get('providers', [ElectricityController::class, 'providers']);
            Route::post('validate', [ElectricityController::class, 'validateMeterNumber']);
            Route::post('purchase', [ElectricityController::class, 'purchase']);
        });

        Route::prefix('airtime')->group(function () {
            Route::get('providers', [AirtimeController::class, 'providers']);
            Route::post('purchase', [AirtimeController::class, 'purchase']);
        });

        Route::prefix('data')->group(function () {
            Route::get('providers', [DataController::class, 'providers']);
            Route::get('{provider}/bundles', [DataController::class, 'dataBundle']);
            Route::post('purchase', [DataController::class, 'purchase']);
        });
    });
});

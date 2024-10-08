<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use App\Rules\Phone;
use App\Services\Helpers\ApiResponse;
use App\Services\ThirdPartyAPIs\CrystalPayApis;
use App\Services\ThirdPartyAPIs\MonnifyApis;
use App\Services\ThirdPartyAPIs\SageCloudServices;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class RegisterController extends Controller
{
    protected function rules(): array
    {
        return [
            'firstname' => 'required|string',
            'lastname' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'gender' => ['required', 'string', Rule::in(['Male', 'Female'])],
            'phone' => ['required', 'unique:users,phone', new Phone],
            'device_id' => 'required|string',
            'date_of_birth' => 'required|date',
//            'transaction_pin' => 'required|string|digits:4',

            'password' => 'required|min:8|max:20',
        ];
    }

    public function validatePhone(Request $request) {
        $request->validate([
            'phone' => ['required', 'string', 'unique:users,phone', new Phone],
        ]);

        // send message

        return ApiResponse::success("SMS sent successfully");
    }

    public function verifyPhoneOtp(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'unique:users,phone', new Phone],
            'otp' => ['required', 'string', 'digits:6'],
        ]);

        //validate otp
        return ApiResponse::success("Phone number verified successfully");
    }

    public function validateEmail(Request $request) {
        $request->validate([
            'email' => ['required', 'email', 'unique:users,email'],
        ]);

        // send messag

        return ApiResponse::success("Email sent successfully");
    }

    public function verifyEmailOtp(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'unique:users,email'],
            'otp' => ['required', 'string', 'digits:6'],
        ]);

        //validate otp
        return ApiResponse::success("Email verified successfully");
    }

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules());
        $validated['password'] = Hash::make($validated['password']);
//        $validated['transaction_pin'] = sha1($validated['transaction_pin']);

        $user = User::create($validated);
        self::createWallet($user);

        $token = $user->createToken($request->email)->plainTextToken;
        $data = ['token' => $token];

        return ApiResponse::success('Account created successfully', $data);
    }

    protected static function createWallet(User $user): void
    {
        $wallet = $user->wallet()->create();
//        self::createMonnifyVirtualAccount($user, $wallet);
//        self::createCrystalPayVirtualAccount($user, $wallet);
        self::createSageCloudVirtualAccount($user, $wallet);

    }

    protected static function createMonnifyVirtualAccount(User $user, Wallet $wallet)
    {
        $monnifyApis = resolve(MonnifyApis::class);

        $payload = [
            "accountReference" => \Str::random(14),
            "accountName" => sprintf('%s %s', $user->firstname, $user->lastname),
            "currencyCode" => "NGN",
            "customerEmail" => $user->email,
            "bvn" => "21212121212",
            "customerName" => sprintf('%s %s', $user->firstname, $user->lastname),
            "getAllAvailableBanks" => false,
            "preferredBanks" => ["035"]
        ];

        $response = $monnifyApis->createVirtualAccount($payload);

        Log::info("creating monnify virtual account", $response);

        if ($response['requestSuccessful']) {
            $accounts = $response['responseBody']['accounts'];
            foreach ($accounts as $account) {
                $wallet->virtualAccount()->create([
                    'user_id' => $user->id,
                    'account_name' => $account['accountName'],
                    'account_number' => $account['accountNumber'],
                    'bank_name' => $account['bankName'],
                    'bank_code' => $account['bankCode'],
                    'provider' => 'MONNIFY',
                    'account_reference' => $response['responseBody']['accountReference'],
                ]);
            }

        } else {
            Log::info("failed response from create virtual account from monnify", $response);
        }
    }
    protected static function createSageCloudVirtualAccount(User $user, Wallet $wallet)
    {
        $sageCloudVirtualAccount = new SageCloudServices(isV3: true);

        $payload = [
            "account_name" => sprintf('%s %s', $user->firstname, $user->lastname),
            "email" => $user->email,
        ];

        $response = $sageCloudVirtualAccount->createVirtualAccount($payload);

        Log::info("creating sagecloud virtual account", $response);

        if (isset($response['status']) && $response['status'] == 'success') {
            $account = $response['data']['account_details'];
                $wallet->virtualAccount()->create([
                    'user_id' => $user->id,
                    'account_name' => $account['account_name'],
                    'account_number' => $account['account_number'],
                    'bank_name' => $account['bank_name'],
                    'provider' => 'SAGECLOUD',
                    'account_reference' => $account['account_reference'],
                ]);
        } else {
            Log::info("failed response from create virtual account from sagecloud", $response);
        }
    }
    protected static function createCrystalPayVirtualAccount(User $user, Wallet $wallet)
    {
        $crystalPayApis = resolve(CrystalPayApis::class);

        $details = [
            "firstname" => $user->firstname,
            "lastname" => $user->lastname,
            "email" => $user->email,
            "virtual_account_package" => ["7", "3"],
            "bvn" => "0123456789"
        ];

        $response = $crystalPayApis->createVirtualAccount($details);

        Log::info("response from CrystalPay virtual account generation", $response);

    }

}

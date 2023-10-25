<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\Phone;
use App\Services\Helpers\ApiResponse;
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
            'transaction_pin' => 'required|string|digits:4',

            'password' => 'required|min:8|max:20',
        ];
    }

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules());
        $validated['password'] = Hash::make($validated['password']);
        $validated['transaction_pin'] = sha1($validated['transaction_pin']);

        $user = User::create($validated);
        self::createWallet($user);

        $token = $user->createToken($request->email)->plainTextToken;
        $data = ['token' => $token];

        return ApiResponse::success('Account created successfully', $data);
    }

    protected static function createWallet(User $user): void
    {
        $wallet = $user->wallet()->create();

//        $payload = [
//            'account_name' => sprintf('%s%s%s', $user->firstname, ' ', $user->lastname),
//            'email' => $user->email,
//        ];
//
//        $response = self::sageCloudServices()->createVirtualAccount($payload);
//        $accountDetails = $response['data']->account_details;
//
//        if ($response['status'] == 'success') {
            $wallet->virtualAccount()->create([
                'user_id' => $user->id,
                'account_name' => $user->firstname . " " . $user->lastname,
                'account_number' => rand(1000000000, 9999999999),
                'bank_name' => 'Test Bank',
                'account_reference' => \Str::random(17),
            ]);
//        } else {
//            Log::info("failed response from create virtual account from sagecloud", $response);
//        }
    }

}

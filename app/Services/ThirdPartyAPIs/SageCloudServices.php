<?php

namespace App\Services\ThirdPartyAPIs;

//use Capiflex\SageCloud\API\SageCloud;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SageCloudServices
{
//    public SageCloud $sageCloud;

    private const BASE_URL = 'https://sagecloud.ng/api';

    private const PURCHASE_AIRTIME_URL = '/v2/airtime';

    private const FETCH_DATA_PROVIDERS = '/v2/internet/data/fetch-providers';

    private const FETCH_DATA_BUNDLES = '/v2/internet/data/lookup';

    private const PURCHASE_DATA = '/v2/internet/data';

    //    private const SPECTRANET_PIN_LOOKUP = '/v2/internet/data/spectranet/lookup';
    //    private const SPECTRANET_PIN_PURCHASE = '/v2/internet/data/spectranet';
    //
    //    private const SMILE_BUNDLE_LOOKUP = '/v2/internet/data/smile/lookup';
    //    private const SMILE_CUSTOMER_VALIDATION = '/v2/internet/data/smile/validate';
    //    private const SMILE_BUNDLE_PURCHASE = '/v2/internet/data/smile';

    private const FETCH_CABLETV_PROVIDERS = '/v2/cable-tv/fetch-providers';

    private const VALIDATE_CABLETV_SMARTCARD = '/v2/cable-tv/validate-customer';

    private const FETCH_CABLETV_BILLERS_FOR_PROVIDERS = '/v2/cable-tv/fetch-billers?type={service_type}';

    private const PURCHASE_CABLETV = '/v2/cable-tv/purchase';

    private const FETCH_POWER_BILLERS = '/v2/electricity/fetch-billers';

    private const VALIDATE_POWER_METER = '/v2/electricity/validate-customer';

    private const PURCHASE_POWER = '/v2/electricity/purchase';

    private const FETCH_BANKS = '/v2/transfer/get-transfer-data';

    private const VERIFY_BANK_DETAILS = '/v2/transfer/verify-bank-account';

    private const TRANSFER_FUNDS = '/v2/transfer/fund-transfer';

    public const SME_DATA_LOOKUP = '/v2/sme-data/list';

    public const SME_DATA_PURCHASE = '/v2/sme-data/purchase';

    private const PURCHASE_EPIN = '/v2/epin/purchase';

    private const REQUERY = '/v2/transaction/requery';

    private const FETCH_BETTING_BILLERS = '/v2/betting/billers';

    private const VALIDATE_BETTING = '/v2/betting/validate';

    private const FUND_BETTING = '/v2/betting/payment';

    private const WAEC_LOOKUP = '/v2/education/waec-lookup';

    private const WAEC_PIN_PURCHASE = '/v2/education/waec-purchase';

    private const JAMB_LOOKUP = '/v2/education/jamb-pricing-options';

    private const JAMB_PROFILE_VALIDATE = '/v2/education/jamb-profile-code/validate';

    private const JAMB_PIN_PURCHASE = '/v2/education/jamb-pin-purchase';

    private const BVN_VERIFICATION = '/v2/kyc/verify-bvn';

    /**
     * @var array<string, string>
     */
    private $network_codes = [
        'mtn' => 'MTN',
        'airtel' => 'AIRTEL',
        'glo' => 'GLO',
        '9mobile' => '9MOBILE',
    ];

    /**
     * @var array<string, string>
     */
    private $service_code = [
        'mtn' => 'MTNVTU',
        'airtel' => 'AIRTELVTU',
        'glo' => 'GLOVTU',
        '9mobile' => '9MOBILEVTU',
    ];

    private $email;

    private $password;

    private $access_token;

    public function __construct($isV3 = false)
    {
        if ($isV3) {
            $email = config('sagecloud.email');
            $password = config('sagecloud.password');
            $secret_key = config('sagecloud.secret_key');

//            $this->sageCloud = new SageCloud($email, $password, $secret_key);

        } else {
            $credentials = [
                'email' => config('sagecloud.email'),
                'password' => config('sagecloud.password'),
            ];

            $this->email = $credentials['email'];
            $this->password = $credentials['password'];

            //check for existing token in cache
            $sageCloudKey = Cache::get('sage-cloud-key');

            if (empty($sageCloudKey)) {
                $this->getToken();

                return;
            }

            $expires_at = Carbon::parse($sageCloudKey['expires_at']);

            if (now()->diffInHours($expires_at) <= 2) {
                $this->getToken();

                return;
            }
            $this->access_token = $sageCloudKey['access_token'];
        }
    }

    /**
     * @param  array  $params array<string, string> ['reference' => <string>, 'network' => <string>, 'service' => <string>, 'phone' => <string>, 'amount' => <string>]
     */
    public function purchaseAirtime(array $params): array
    {
        $url = sprintf('%s%s', self::BASE_URL, self::PURCHASE_AIRTIME_URL);

        return $this->post($url, $params);
    }

    public function fetchDataProviders(): array
    {
        $url = sprintf('%s%s', self::BASE_URL, self::FETCH_DATA_PROVIDERS);

        return $this->get($url);
    }

    /**
     * @param array $params array<string, string> ['provider' => <string>]
     */
    public function fetchDataBundles(array $params): array
    {
        $url = sprintf('%s%s', self::BASE_URL, self::FETCH_DATA_BUNDLES);

        return $this->get($url, $params);
    }

    /**
     * @param array $params array<string, string> ['reference' => <string>, 'type' => <string>, 'network' => <string>, 'phone' => <string>, 'provider' => <string>, 'code' => <string>]
     */
    public function purchaseData(array $params): array
    {
        $url = sprintf('%s%s', self::BASE_URL, self::PURCHASE_DATA);

        return $this->post($url, $params);
    }

    public function handleSMEDataLookup(): array
    {
        $url = sprintf('%s%s', self::BASE_URL, self::SME_DATA_LOOKUP);

        return $this->get($url);
    }

    /**
     * @param array $params array<string string> ['service' => <string>, 'phone' => <string>, 'reference' => 'string']
     */
    public function handleSMEDataPurchase(array $params): array
    {
        $url = sprintf('%s%s', self::BASE_URL, self::SME_DATA_PURCHASE);

        return $this->post($url, $params);
    }

    public function reQuery($reference)//: array
    {
        $url = sprintf('%s%s', self::BASE_URL, self::REQUERY);
        $res = Http::withToken($this->access_token)->post($url, ['reference' => $reference]);

        return $this->response($res);
    }

    public function verifyBvn($data): array
    {
        $url = sprintf('%s%s', self::BASE_URL, self::BVN_VERIFICATION);
        $res = Http::withToken($this->access_token)->post($url, $data);

        return $this->response($res);
    }

    public function fetchCableTvProviders(): array
    {
        $url = sprintf('%s%s', self::BASE_URL, self::FETCH_CABLETV_PROVIDERS);
        $res = Http::withToken($this->access_token)->get($url);

        return $this->response($res);
    }

    public function fetchCableTvBillersForProvider($type): array
    {
        $url = sprintf('%s/v2/cable-tv/fetch-billers?type=%s', self::BASE_URL, $type);
        $res = Http::withToken($this->access_token)->get($url);

        return $this->response($res);
    }

    public function validateSmartcard(array $params): array
    {
        $url = sprintf('%s%s', self::BASE_URL, self::VALIDATE_CABLETV_SMARTCARD);
        $res = Http::withToken($this->access_token)->post($url, $params);

        return $this->response($res);
    }

    /**
     * @param array $params array<string string> ['reference' => <string>, 'code' => <string>, 'smartCardNo' => <string>, 'type' => <string>, 'renewal' => <string>]
     */
    public function purchaseCableTv(array $params): array
    {
        $url = sprintf('%s%s', self::BASE_URL, self::PURCHASE_CABLETV);
        $res = Http::withToken($this->access_token)->post($url, $params);

        return $this->response($res);
    }

    public function fetchBanks()
    {
        $url = sprintf('%s%s', self::BASE_URL, self::FETCH_BANKS);
        $res = Http::withToken($this->access_token)->get($url);

        return $this->response($res);
    }

    /**
     * @param array $params array<string string>
     *  ['bank_code' => <string>, 'account_number' => <string>]
     */
    public function verifyBankDetails(array $params): array
    {
        $url = sprintf('%s%s', self::BASE_URL, self::VERIFY_BANK_DETAILS);
        $res = Http::withToken($this->access_token)->post($url, $params);

        return $this->response($res);
    }

    /**
     * @param array $params array<string string>
     *  ['reference' => <string>, 'bank_code' => <string>, 'account_number' => <string>, 'account_name' => <string>, 'amount' => <string>, 'narration' => <string>]
     */
    public function transferFunds(array $params): array
    {
        $url = sprintf('%s%s', self::BASE_URL, self::TRANSFER_FUNDS);
        $res = Http::withToken($this->access_token)->post($url, $params);

        return $this->response($res);
    }

    public function fetchElectricityBillers()
    {
        $url = sprintf('%s%s', self::BASE_URL, self::FETCH_POWER_BILLERS);

        return $this->get($url);
    }

    /**
     * @param array $params array<string, string>['account_number' => <string>, 'type' => <string>]
     * @return array|mixed
     */
    public function validateMeter(array $params)
    {
        $url = sprintf('%s%s', self::BASE_URL, self::VALIDATE_POWER_METER);

        return $this->post($url, $params);
    }

    /**
     * @param array $params array<string, string>[
     *      'reference' => <string>,
     *      'type' => <string>,
     *      'account_number' => <string>,
     *      'amount' => <string>,
     *      'phone' => <string>
     *  ]
     * @return array|mixed
     */
    public function purchasePower(array $params): array
    {
        $url = sprintf('%s%s', self::BASE_URL, self::PURCHASE_POWER);

        return $this->post($url, $params);
    }

    /**
     * @param array $params array<string, string>[
     *          'reference' => <string>,
     *          'network' => <string>
     *          'service' => <string>,
     *          'value' => <string>,
     *          'quantity' => <string>
     *      ]
     */
    public function purchaseEpin(array $params): array
    {
        $url = sprintf('%s%s', self::BASE_URL, self::PURCHASE_EPIN);

        return $this->post($url, $params);
    }

    public function fetchBettingBillers(): array
    {
        $url = sprintf('%s%s', self::BASE_URL, self::FETCH_BETTING_BILLERS);

        return $this->get($url);
    }

    /**
     * @param array $params array<string string>['type' => string, 'customerId' => string]
     */
    public function validateBetting(array $params): array
    {
        $url = sprintf('%s%s', self::BASE_URL, self::VALIDATE_BETTING);

        return $this->post($url, $params);
    }

    /**
     * @param array $params array<string string>['reference' => <string>, 'type' => <string>, 'customerId' => <string>, 'name' => <string>, 'amount' => <string>]
     */
    public function fundBetting(array $params): array
    {
        $url = sprintf('%s%s', self::BASE_URL, self::FUND_BETTING);

        return $this->post($url, $params);
    }

    public function handleWAECLookup(): array
    {
        $url = sprintf('%s%s', self::BASE_URL, self::WAEC_LOOKUP);

        return $this->get($url);
    }

    public function handleWAECPinPurchase(array $params): array
    {
        $url = sprintf('%s%s', self::BASE_URL, self::WAEC_PIN_PURCHASE);

        return $this->post($url, $params);
    }

    public function handleJAMBLookup(): array
    {
        $url = sprintf('%s%s', self::BASE_URL, self::JAMB_LOOKUP);

        return $this->get($url);
    }

    public function handleJAMBProfileValidation(array $params): array
    {
        $url = sprintf('%s%s', self::BASE_URL, self::JAMB_PROFILE_VALIDATE);

        return $this->post($url, $params);
    }

    public function handleJAMBPinPurchase(array $params): array
    {
        $url = sprintf('%s%s', self::BASE_URL, self::JAMB_PIN_PURCHASE);

        return Http::withToken($this->access_token)->post($url, $params)->json();
    }

    protected function post(string $url, array $params): array
    {
        $res = Http::withToken($this->access_token)->post($url, $params);

        return $this->response($res);
    }

    protected function get(string $url, $params = null): array
    {
        $url = $params ? $url . '?' . http_build_query($params) : $url;
        $res = Http::withToken($this->access_token)->get($url);

        return $this->response($res);
    }

//    public function createVirtualAccount($payload): array
//    {
//        return $this->sageCloud->generateVirtualAccount($payload);
//    }

    private function response(Response $response): array
    {
        return $response->ok() && $response->json() ? $response->json() : [];
    }

    private function getToken(): void
    {
        $url = static::BASE_URL . '/v2/merchant/authorization';
        $res = Http::post($url, [
            'email' => $this->email,
            'password' => $this->password,
        ]);

        if (($response = $res->json()) && $res->ok()) {
            if ($response['success']) {
                $access_token = $response['data']['token']['access_token'];
                $expires_at = Carbon::parse($response['data']['token']['expires_at']);
                $body = [
                    'access_token' => $access_token,
                    'expires_at' => $expires_at,
                ];
                Cache::put('sage-cloud-key', $body, $expires_at);
                $this->access_token = $body['access_token'];

                return;
            }
            //LogToSlack::dispatch('auth-error-from-sagecloud', $response);
        }
    }
}

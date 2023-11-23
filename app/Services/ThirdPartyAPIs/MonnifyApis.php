<?php

namespace App\Services\ThirdPartyAPIs;

use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class MonnifyApis
{
    private const CREATE_VIRTUAL_ACCOUNT = '/api/v2/bank-transfer/reserved-accounts';

    private string $baseUrl;
    private string $apiKey;

    private string $secretKey;

    private string $access_token;
    private string $contractCode;

    public function __construct()
    {
        $credentials = [
            'baseUrl' => config('services.monnify.base_url'),
            'apiKey' => config('services.monnify.api_key'),
            'secretKey' => config('services.monnify.secret_key'),
            'contractCode' => config('services.monnify.contract_code')
        ];

        $this->baseUrl = $credentials['baseUrl'];
        $this->apiKey = $credentials['apiKey'];
        $this->secretKey = $credentials['secretKey'];
        $this->contractCode = $credentials['contractCode'];

        //check for existing token in cache
        $monnifyKey = Cache::get('monnify-key');

        if (empty($monnifyKey)) {
            $this->getToken();
            return;
        }

        $expires_at = Carbon::parse($monnifyKey['expires_at']);

        if (now()->diffInHours($expires_at) <= 2) {
            $this->getToken();

            return;
        }
        $this->access_token = $monnifyKey['access_token'];

    }

    /**
     * @param array $params
     * @return array
     */
    public function createVirtualAccount(array $params): array
    {
        $params['contractCode'] = $this->contractCode;
        $url = sprintf('%s%s', $this->baseUrl, self::CREATE_VIRTUAL_ACCOUNT);
        return $this->post($url, $params);
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

    private function response(Response $response): array
    {
        \Log::info("log from response", $response->json() ?? [$response]);
        return ($response->ok() || $response->created()) && $response->json() ? $response->json() : [];
    }

    private function getToken(): void
    {
        $url = $this->baseUrl . '/api/v1/auth/login';
        $header = [
            'Authorization' => 'Basic '.base64_encode(sprintf('%s:%s', $this->apiKey, $this->secretKey)),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];
        $res = Http::withHeaders($header)->post($url);

        if (($response = $res->json()) && $res->ok()) {
            if ($response['requestSuccessful']) {
                $access_token = $response['responseBody']['accessToken'];
                $expires_at = now()->addSeconds($response['responseBody']['expiresIn']);
                $body = [
                    'access_token' => $access_token,
                    'expires_at' => $expires_at,
                ];
                Cache::put('monnify-key', $body, $expires_at);
                $this->access_token = $body['access_token'];

                return;
            }
            \Log::info('auth-error-from-monnify', $response);
        }
    }
}

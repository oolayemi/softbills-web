<?php

namespace App\Services\ThirdPartyAPIs;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SageCloudV3
{

    private const BASE_URL = 'https://sagecloud.ng/api';
    private const CREATE_VIRTUAL_ACCOUNT = '/v3/virtual-account/generate';
    private string $secretKey;

    public function __construct()
    {
        $this->secretKey = config('sagecloud.secret_key');
    }

    protected function post(string $url, array $params): array
    {
        $res = Http::withHeaders(['Authorization' => $this->secretKey])
            ->acceptJson()
            ->post($url, $params);
        return $this->response($res);
    }

    protected function get(string $url, $params = null): array
    {
        $url = $params ? $url . '?' . http_build_query($params) : $url;
        $res = Http::withHeaders(['Authorization' => $this->secretKey])
            ->acceptJson()
            ->get($url);

        return $this->response($res);
    }

    private function response(Response $response): array
    {
        if ($response->ok() && $response->json()) {
            return $response->json();
        } else {
            Log::info('error from sagecloudV3 action', $response->json() ?? [$response]);

            return [];
        }
    }

    /**
     * @param array $params array<string, string>['account_name' => <string>, 'email' => <string>]
     * @return array
     */
    public function generateVirtualAccount(array $params): array
    {
        $url = sprintf('%s%s', self::BASE_URL, self::CREATE_VIRTUAL_ACCOUNT);
        return $this->post($url, $params);
    }
}

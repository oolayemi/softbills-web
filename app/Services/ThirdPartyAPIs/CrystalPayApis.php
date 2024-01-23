<?php

namespace App\Services\ThirdPartyAPIs;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class CrystalPayApis
{
    private const CREATE_VIRTUAL_ACCOUNT = '/virtual-account';

    private string $baseUrl;
    private string $secretKey;

    public function __construct()
    {
        $this->baseUrl = config('services.crystalpay.base_url');
        $this->secretKey = config('services.crystalpay.secret_key');
    }

    protected function headers(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'secret_key' => $this->secretKey,
        ];
    }

    public function createVirtualAccount(array $params): array
    {
        $url = sprintf('%s%s', $this->baseUrl, self::CREATE_VIRTUAL_ACCOUNT);
        return $this->post($url, $params);
    }

    protected function post(string $url, array $params): array
    {
        $res = Http::withHeaders($this->headers())->post($url, $params);
        return $this->response($res);
    }

    protected function get(string $url, $params = null): array
    {
        $url = $params ? $url . '?' . http_build_query($params) : $url;
        $res = Http::withHeaders($this->headers())->get($url);
        return $this->response($res);
    }

    private function response(Response $response): array
    {
        if ($response->ok() && $response->json()) {
            return $response->json();
        }
        \Log::error("An error occurred from Mega Sub Plug", [$response]);
        return [];
    }
}

<?php

namespace App\Services\ThirdPartyAPIs;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class MegaSubApis
{
    private string $baseUrl;
    private string $token;
    private string $password;
    private string $buyAirtime = '/?action=buy_airtime';
    private string $buyData = '/?action=buy_data';

    public function __construct()
    {
        $this->setValues();
    }

    protected function headers(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => sprintf("%s",$this->token),
            "Password" => sprintf('%s',$this->password)
        ];
    }

    public function purchaseAirtime(array $params): array
    {
        $url = sprintf('%s%s', $this->baseUrl, $this->buyAirtime);
        return $this->post($url, $params);
    }

    public function purchaseData(array $params): array
    {
        $url = sprintf('%s%s', $this->baseUrl, $this->buyData);
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

    protected function setValues(): void
    {
        $this->baseUrl = config('services.megasub.base_url');
        $this->token = config('services.megasub.username');
        $this->password = config('services.megasub.password');
    }
}

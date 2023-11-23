<?php

namespace App\Services\ThirdPartyAPIs;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class VtPassApis
{
    private string $baseUrl;
    private string $username;
    private string $password;
    private string $tvProviders = '/services?identifier=tv-subscription';
    private string $airtimeProviders = '/services?identifier=airtime';
    private string $dataProviders = '/services?identifier=data';
    private string $electricityProviders = '/services?identifier=electricity-bill';
    private string $serviceVariations = '/service-variations?serviceID=';
    private string $merchantVerify = '/merchant-verify';
    private string $payment = '/pay';

    public function __construct()
    {
        $this->setValues();
    }

    protected function headers(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => sprintf('%s:%s',$this->username,$this->password)
        ];
    }

    public function fetchAirtimeProviders(): array
    {
        $url = sprintf('%s%s', $this->baseUrl, $this->airtimeProviders);
        return $this->get($url);
    }

    public function fetchDataProviders(): array
    {
        $url = sprintf('%s%s', $this->baseUrl, $this->dataProviders);
        return $this->get($url);
    }

    public function fetchDataBundles(string $type): array
    {
        $url = sprintf('%s%s%s', $this->baseUrl, $this->serviceVariations, $type);
        return $this->get($url);
    }

    public function fetchTvProviders(): array
    {
        $url = sprintf('%s%s', $this->baseUrl, $this->tvProviders);
        return $this->get($url);
    }

    public function fetchTvBillersForProvider(string $type): array
    {
        $url = sprintf('%s%s%s', $this->baseUrl, $this->serviceVariations, $type);
        return $this->get($url);
    }

    public function fetchElectricityProviders(): array
    {
        $url = sprintf('%s%s', $this->baseUrl, $this->electricityProviders);
        return $this->get($url);
    }

    public function validateSmartCard(array $params): array
    {
        $url = sprintf('%s%s', $this->baseUrl, $this->merchantVerify);
        return $this->post($url, $params);
    }

    public function purchaseCableTv(array $params): array
    {
        $url = sprintf('%s%s', $this->baseUrl, $this->payment);
        return $this->post($url, $params);
    }

    public function validateMeterNumber(array $params): array
    {
        $url = sprintf('%s%s', $this->baseUrl, $this->merchantVerify);
        return $this->post($url, $params);
    }

    public function merchantPayment(array $params): array
    {
        $url = sprintf('%s%s', $this->baseUrl, $this->payment);
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
        \Log::error("An error occurred from vtpass", [$response]);
        return [];
    }

    protected function setValues(): void
    {
        $this->baseUrl = config('services.vtpass.base_url');
        $this->username = config('services.vtpass.username');
        $this->password = config('services.vtpass.password');
    }
}

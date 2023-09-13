<?php

namespace App\Services\ThirdPartyAPIs;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class PaystackApis
{
    private string $baseUrl;

    private string $secretKey;
    private string $preferredBank = 'test-bank';

    private string $createCustomer = '/customer';
    private string $createVirtualAccount = '/dedicated_account';
    private string $initiatePayment = '/transaction/initialize';
    private string $verifyPayment = '/transaction/verify/';

    public function __construct()
    {
        $this->setValues();
    }

    protected function headers(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->secretKey
        ];
    }

    public function createCustomer(array $params): array
    {
        $url = sprintf('%s%s', $this->baseUrl, $this->createCustomer);
        $response = $this->post($url, $params);

        if (empty($response)) return [];

        $customerId = $response['data']['customer_code'];
        return $this->createVirtualAccount($customerId);
    }

    public function initiateTransaction(array $params): array
    {
        $url = sprintf('%s%s', $this->baseUrl, $this->initiatePayment);
        $response = $this->post($url, $params);

        \Log::info("transaction response", $response);

        if (empty($response)) return [];
        return $response;
    }

    public function verifyTransaction(string $reference): array
    {
        $url = sprintf('%s%s%s', $this->baseUrl, $this->verifyPayment, $reference);
        $response = $this->get($url);

        \Log::info("transaction verification response", $response);

        if (empty($response)) return [];
        return $response;
    }

    protected function createVirtualAccount(string $customerId): array
    {
        $url = sprintf('%s%s', $this->baseUrl, $this->createVirtualAccount);
        $payload = ['customer' => $customerId, 'preferred_bank' => $this->preferredBank];

        return $this->post($url, $payload);
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
        \Log::error("An error occurred from paystack", [$response]);
        return [];
    }

    protected function setValues(): void
    {
        $this->baseUrl = config('services.paystack.base_url');
        $this->secretKey = config('services.paystack.secret_key');
    }
}

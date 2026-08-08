<?php

namespace App\Services;

use App\Exceptions\GatewayException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GatewayService
{
    protected string $baseUrl;
    protected string $secret;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.gateway.url'), '/');
        $this->secret = config('services.gateway.secret');
    }

    public function charge(array $data, ?string $idempotencyKey = null): array
    {
        $headers = ['Content-Type' => 'application/json'];
        if ($idempotencyKey) {
            $headers['Idempotency-Key'] = $idempotencyKey;
        }

        $response = Http::withHeaders($headers)->post($this->baseUrl . '/charge', $data);
        return $this->handleResponse($response);
    }

    public function refund(string $paymentId): array
    {
        $response = Http::post($this->baseUrl . '/refund', ['payment_id' => $paymentId]);
        return $this->handleResponse($response);
    }

    public function sendOtp(array $data): array
    {
        $response = Http::post($this->baseUrl . '/otp/send', $data);
        return $this->handleResponse($response);
    }

    public function verifyOtp(array $data): array
    {
        $response = Http::post($this->baseUrl . '/otp/verify', $data);
        return $this->handleResponse($response);
    }

    protected function handleResponse($response): array
    {
        if ($response->failed()) {
            Log::warning('Mock Gateway request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new GatewayException('Gateway responded with error: ' . $response->status(), $response->status());
        }
        return $response->json();
    }

    public function getSecret(): string
    {
        return $this->secret;
    }
}
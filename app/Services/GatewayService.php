<?php

namespace App\Services;

use App\Exceptions\GatewayException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GatewayService
{
    protected string $baseUrl;
    protected string $secret;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.gateway.url'), '/');
        $this->secret = config('services.gateway.secret');
        $this->timeout = (int) config('services.gateway.timeout', 10);
    }

    /**
     * Initiate a payment charge.
     *
     * @param array $data Must contain: amount, currency, booking_ref, callback_url
     * @param string|null $idempotencyKey Optional: prevent duplicate charges on retry
     * @param array $extraHeaders Optional: additional HTTP headers (e.g., X-Mock-Force)
     * @return array The gateway response (202 with payment_id and status PENDING)
     * @throws GatewayException
     */
    public function charge(array $data, ?string $idempotencyKey = null, array $extraHeaders = []): array
    {
        $headers = array_merge([
            'Content-Type' => 'application/json',
        ], $extraHeaders);

        if ($idempotencyKey) {
            $headers['Idempotency-Key'] = $idempotencyKey;
        }

        $response = Http::timeout($this->timeout)
            ->withHeaders($headers)
            ->post($this->baseUrl . '/charge', $data);

        return $this->handleResponse($response);
    }

    /**
     * Refund a previously succeeded payment.
     *
     * @param string $paymentId The gateway's payment_id (e.g., pay_abc123)
     * @param array $extraHeaders Optional: additional HTTP headers
     * @return array
     * @throws GatewayException
     */
    public function refund(string $paymentId, array $extraHeaders = []): array
    {
        $headers = array_merge([
            'Content-Type' => 'application/json',
        ], $extraHeaders);

        $response = Http::timeout($this->timeout)
            ->withHeaders($headers)
            ->post($this->baseUrl . '/refund', [
                'payment_id' => $paymentId
            ]);

        return $this->handleResponse($response);
    }

    /**
     * Request an OTP to be sent to the user's phone.
     *
     * @param array $data Must contain: phone, ref (booking_ref), callback_url
     * @param array $extraHeaders Optional: additional HTTP headers
     * @return array
     * @throws GatewayException
     */
    public function sendOtp(array $data, array $extraHeaders = []): array
    {
        $headers = array_merge([
            'Content-Type' => 'application/json',
        ], $extraHeaders);

        $response = Http::timeout($this->timeout)
            ->withHeaders($headers)
            ->post($this->baseUrl . '/otp/send', $data);

        return $this->handleResponse($response);
    }

    /**
     * Verify the OTP code provided by the user.
     *
     * @param array $data Must contain: ref (booking_ref), code
     * @param array $extraHeaders Optional: additional HTTP headers
     * @return array Contains ['verified' => true/false]
     * @throws GatewayException
     */
    public function verifyOtp(array $data, array $extraHeaders = []): array
    {
        $headers = array_merge([
            'Content-Type' => 'application/json',
        ], $extraHeaders);

        $response = Http::timeout($this->timeout)
            ->withHeaders($headers)
            ->post($this->baseUrl . '/otp/verify', $data);

        return $this->handleResponse($response);
    }

    /**
     * Centralized response handler for all gateway calls.
     *
     * The gateway spec says:
     * - 2% of /charge requests return 500. We MUST catch that.
     * - Anything non-2xx should be treated as a failure.
     *
     * @param \Illuminate\Http\Client\Response $response
     * @return array
     * @throws GatewayException
     */
    protected function handleResponse($response): array
    {
        if ($response->failed()) {
            Log::warning('Mock Gateway request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'headers' => $response->headers(),
            ]);

            throw new GatewayException(
                'Gateway responded with error: ' . $response->status(),
                $response->status()
            );
        }

        return $response->json();
    }

    /**
     * Helper to access the secret for HMAC verification.
     *
     * @return string
     */
    public function getSecret(): string
    {
        return $this->secret;
    }
}
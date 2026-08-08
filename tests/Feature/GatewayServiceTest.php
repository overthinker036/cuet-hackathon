<?php

namespace Tests\Feature;

use App\Exceptions\GatewayException;
use App\Services\GatewayService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GatewayServiceTest extends TestCase
{
    protected GatewayService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(GatewayService::class);
    }

    public function test_can_charge_with_deterministic_mode()
    {
        $response = $this->service->charge([
            'amount' => 450,
            'currency' => 'BDT',
            'booking_ref' => 'test_det_' . time(),
            'callback_url' => 'http://localhost:3000/webhooks/payment'
        ], 'det-key-' . time(), ['X-Mock-Mode' => 'deterministic']);

        $this->assertArrayHasKey('payment_id', $response);
        $this->assertEquals('PENDING', $response['status']);
    }

    public function test_is_idempotent()
    {
        $idempotencyKey = 'idempotent-key-' . time();

        $first = $this->service->charge([
            'amount' => 450,
            'currency' => 'BDT',
            'booking_ref' => 'test_idem',
            'callback_url' => 'http://localhost:3000/webhooks/payment'
        ], $idempotencyKey, ['X-Mock-Mode' => 'deterministic']);

        $second = $this->service->charge([
            'amount' => 450,
            'currency' => 'BDT',
            'booking_ref' => 'test_idem',
            'callback_url' => 'http://localhost:3000/webhooks/payment'
        ], $idempotencyKey, ['X-Mock-Mode' => 'deterministic']);

        $this->assertEquals($first['payment_id'], $second['payment_id']);
        $this->assertEquals('PENDING', $second['status']);
    }

    public function test_can_send_and_verify_otp()
    {
        $ref = 'otp_test_' . time();

        // Send OTP with deterministic mode
        $sendResponse = $this->service->sendOtp([
            'phone' => '01700000000',
            'ref' => $ref,
            'callback_url' => 'http://localhost:3000/webhooks/otp'
        ], ['X-Mock-Mode' => 'deterministic']);

        $this->assertIsArray($sendResponse);

        // Wait for gateway to process (deterministic mode sends quickly)
        sleep(3);

        // Verify with deterministic code 123456
        $verifyResponse = $this->service->verifyOtp([
            'ref' => $ref,
            'code' => '123456'
        ], ['X-Mock-Mode' => 'deterministic']);

        $this->assertTrue($verifyResponse['verified']);
    }

    public function test_throws_exception_on_500_error()
    {
        Http::fake([
            '*/charge' => Http::response('Internal Server Error', 500)
        ]);

        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('Gateway responded with error: 500');

        $this->service->charge([
            'amount' => 450,
            'currency' => 'BDT',
            'booking_ref' => 'test_error',
            'callback_url' => 'http://localhost:3000/webhooks/payment'
        ]);
    }

    public function test_throws_exception_on_connection_failure()
    {
        Http::fake(function ($request) {
            throw new \Illuminate\Http\Client\ConnectionException('Connection refused');
        });

        $this->expectException(\Illuminate\Http\Client\ConnectionException::class);

        $this->service->charge([
            'amount' => 450,
            'currency' => 'BDT',
            'booking_ref' => 'test_network',
            'callback_url' => 'http://localhost:3000/webhooks/payment'
        ]);
    }

    public function test_handles_force_success_via_header()
    {
        $response = $this->service->charge([
            'amount' => 450,
            'currency' => 'BDT',
            'booking_ref' => 'force_success_' . time(),
            'callback_url' => 'http://localhost:3000/webhooks/payment'
        ], null, ['X-Mock-Force' => 'success']);

        $this->assertArrayHasKey('payment_id', $response);
        $this->assertEquals('PENDING', $response['status']);
    }

    public function test_handles_force_timeout_via_header()
    {
        $this->expectException(\Illuminate\Http\Client\ConnectionException::class);

        $this->service->charge([
            'amount' => 450,
            'currency' => 'BDT',
            'booking_ref' => 'force_timeout_' . time(),
            'callback_url' => 'http://localhost:3000/webhooks/payment'
        ], null, ['X-Mock-Force' => 'timeout']);
    }
}
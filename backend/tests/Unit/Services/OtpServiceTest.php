<?php

namespace Tests\Unit\Services;

use App\Contracts\SmsGateway;
use App\Services\Auth\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeSmsGateway;
use Tests\TestCase;

class OtpServiceTest extends TestCase
{
    use RefreshDatabase;

    private FakeSmsGateway $sms;

    private OtpService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sms = new FakeSmsGateway;
        $this->app->instance(SmsGateway::class, $this->sms);
        $this->service = $this->app->make(OtpService::class);
    }

    public function test_generated_code_verifies_successfully_exactly_once(): void
    {
        $this->service->generateAndSend('+22241234567');
        $code = $this->sms->lastCodeFor('+22241234567');

        $this->assertTrue($this->service->verify('+22241234567', $code));
        // A consumed code cannot be replayed.
        $this->assertFalse($this->service->verify('+22241234567', $code));
    }

    public function test_requesting_a_new_code_invalidates_the_previous_one(): void
    {
        $this->service->generateAndSend('+22241234567');
        $firstCode = $this->sms->lastCodeFor('+22241234567');

        $this->service->generateAndSend('+22241234567');

        $this->assertFalse($this->service->verify('+22241234567', $firstCode));
    }

    public function test_verification_locks_out_after_too_many_wrong_attempts(): void
    {
        $this->service->generateAndSend('+22241234567');

        for ($i = 0; $i < 5; $i++) {
            $this->assertFalse($this->service->verify('+22241234567', '000000'));
        }

        $correctCode = $this->sms->lastCodeFor('+22241234567');
        $this->assertFalse($this->service->verify('+22241234567', $correctCode));
    }
}

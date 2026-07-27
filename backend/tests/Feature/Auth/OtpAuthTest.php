<?php

namespace Tests\Feature\Auth;

use App\Contracts\SmsGateway;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeSmsGateway;
use Tests\TestCase;

class OtpAuthTest extends TestCase
{
    use RefreshDatabase;

    private FakeSmsGateway $sms;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->sms = new FakeSmsGateway;
        $this->app->instance(SmsGateway::class, $this->sms);
    }

    public function test_requesting_an_otp_sends_a_six_digit_code(): void
    {
        $this->postJson('/api/v1/auth/otp/request', ['phone' => '+22241234567'])
            ->assertOk();

        $this->assertNotNull($this->sms->lastCodeFor('+22241234567'));
    }

    public function test_verifying_a_correct_code_creates_a_new_user_with_the_client_role(): void
    {
        $this->postJson('/api/v1/auth/otp/request', ['phone' => '+22241234567']);
        $code = $this->sms->lastCodeFor('+22241234567');

        $response = $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => '+22241234567',
            'code' => $code,
            'name' => 'Aissata',
        ]);

        $response->assertOk()->assertJsonPath('user.name', 'Aissata');

        $user = User::query()->where('phone', '+22241234567')->firstOrFail();
        $this->assertTrue($user->hasRole('client'));
        $this->assertNotNull($user->phone_verified_at);
    }

    public function test_verifying_logs_in_an_existing_user_instead_of_creating_a_duplicate(): void
    {
        $this->postJson('/api/v1/auth/otp/request', ['phone' => '+22241234567']);
        $code = $this->sms->lastCodeFor('+22241234567');
        $this->postJson('/api/v1/auth/otp/verify', ['phone' => '+22241234567', 'code' => $code]);

        $this->postJson('/api/v1/auth/otp/request', ['phone' => '+22241234567']);
        $secondCode = $this->sms->lastCodeFor('+22241234567');
        $this->postJson('/api/v1/auth/otp/verify', ['phone' => '+22241234567', 'code' => $secondCode])
            ->assertOk();

        $this->assertSame(1, User::query()->where('phone', '+22241234567')->count());
    }

    public function test_verifying_with_the_wrong_code_is_rejected(): void
    {
        $this->postJson('/api/v1/auth/otp/request', ['phone' => '+22241234567']);

        $this->postJson('/api/v1/auth/otp/verify', ['phone' => '+22241234567', 'code' => '000000'])
            ->assertStatus(422);
    }

    public function test_verifying_an_expired_code_is_rejected(): void
    {
        $this->postJson('/api/v1/auth/otp/request', ['phone' => '+22241234567']);
        $code = $this->sms->lastCodeFor('+22241234567');

        $this->travel(10)->minutes();

        $this->postJson('/api/v1/auth/otp/verify', ['phone' => '+22241234567', 'code' => $code])
            ->assertStatus(422);
    }
}

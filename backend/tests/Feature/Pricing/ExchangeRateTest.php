<?php

namespace Tests\Feature\Pricing;

use App\Models\User;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExchangeRateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(Roles::ADMINISTRATEUR);

        return $admin;
    }

    public function test_an_administrateur_can_record_a_new_rate(): void
    {
        $response = $this->actingAs($this->admin(), 'api')
            ->postJson('/api/v1/admin/exchange-rates', ['rate' => 47.5]);

        $response->assertCreated()->assertJsonPath('data.rate', '47.500000');
        $this->assertDatabaseHas('exchange_rates', ['currency_pair' => 'EUR_MRU']);
    }

    public function test_a_client_cannot_manage_exchange_rates(): void
    {
        $client = User::factory()->create();
        $client->assignRole(Roles::CLIENT);

        $this->actingAs($client, 'api')
            ->postJson('/api/v1/admin/exchange-rates', ['rate' => 47.5])
            ->assertStatus(403);
    }

    public function test_a_negative_or_zero_rate_is_rejected(): void
    {
        $this->actingAs($this->admin(), 'api')
            ->postJson('/api/v1/admin/exchange-rates', ['rate' => 0])
            ->assertStatus(422);
    }
}

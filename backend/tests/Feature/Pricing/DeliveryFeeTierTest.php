<?php

namespace Tests\Feature\Pricing;

use App\Models\DeliveryFeeTier;
use App\Models\User;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryFeeTierTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole(Roles::ADMINISTRATEUR);
    }

    public function test_it_creates_a_tier(): void
    {
        $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/delivery-fee-tiers', [
                'zone' => 'nouakchott',
                'min_price_mru' => 0,
                'max_price_mru' => 1000,
                'fee_mru' => 200,
            ])
            ->assertCreated();
    }

    public function test_max_price_must_exceed_min_price(): void
    {
        $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/delivery-fee-tiers', [
                'zone' => 'nouakchott',
                'min_price_mru' => 1000,
                'max_price_mru' => 500,
                'fee_mru' => 200,
            ])
            ->assertStatus(422);
    }

    public function test_it_updates_and_deletes_a_tier(): void
    {
        $tier = DeliveryFeeTier::query()->create([
            'zone' => 'nouakchott', 'min_price_mru' => 0, 'max_price_mru' => null, 'fee_mru' => 350,
        ]);

        $this->actingAs($this->admin, 'api')
            ->putJson("/api/v1/admin/delivery-fee-tiers/{$tier->id}", ['fee_mru' => 400])
            ->assertOk()
            ->assertJsonPath('data.fee_mru', '400.00');

        $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/v1/admin/delivery-fee-tiers/{$tier->id}")
            ->assertOk();

        $this->assertDatabaseMissing('delivery_fee_tiers', ['id' => $tier->id]);
    }

    public function test_a_client_cannot_manage_delivery_fee_tiers(): void
    {
        $client = User::factory()->create();
        $client->assignRole(Roles::CLIENT);

        $this->actingAs($client, 'api')
            ->getJson('/api/v1/admin/delivery-fee-tiers')
            ->assertStatus(403);
    }
}

<?php

namespace Tests\Feature\Catalog;

use App\Models\Boutique;
use App\Models\Product;
use App\Models\User;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductTest extends TestCase
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

    public function test_it_lists_products_filtered_by_boutique(): void
    {
        $boutiqueA = Boutique::factory()->create();
        $boutiqueB = Boutique::factory()->create();
        Product::factory()->create(['boutique_id' => $boutiqueA->id]);
        Product::factory()->create(['boutique_id' => $boutiqueB->id]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson("/api/v1/admin/products?boutique_id={$boutiqueA->id}");

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_correcting_the_name_locks_the_translation(): void
    {
        $product = Product::factory()->create(['name' => ['fr' => 'Ancien', 'ar' => 'قديم']]);

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/v1/admin/products/{$product->id}", [
                'name' => ['fr' => 'Nouveau', 'ar' => 'جديد'],
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name.fr', 'Nouveau')
            ->assertJsonPath('data.translation_locked', true);
    }

    public function test_a_client_cannot_view_admin_products(): void
    {
        $client = User::factory()->create();
        $client->assignRole(Roles::CLIENT);

        $this->actingAs($client, 'api')
            ->getJson('/api/v1/admin/products')
            ->assertStatus(403);
    }
}

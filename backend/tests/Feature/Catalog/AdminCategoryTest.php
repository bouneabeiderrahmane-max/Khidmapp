<?php

namespace Tests\Feature\Catalog;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCategoryTest extends TestCase
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

    public function test_a_client_cannot_manage_categories(): void
    {
        $client = User::factory()->create();
        $client->assignRole(Roles::CLIENT);

        $this->actingAs($client, 'api')
            ->postJson('/api/v1/admin/categories', ['name' => ['fr' => 'Femme', 'ar' => 'نساء']])
            ->assertStatus(403);
    }

    public function test_an_administrateur_can_create_a_category(): void
    {
        $response = $this->actingAs($this->admin(), 'api')
            ->postJson('/api/v1/admin/categories', ['name' => ['fr' => 'Femme', 'ar' => 'نساء']]);

        $response->assertCreated()->assertJsonPath('data.slug', 'femme');
    }

    public function test_public_listing_exposes_categories(): void
    {
        Category::factory()->create(['name' => ['fr' => 'Homme', 'ar' => 'رجال']]);

        $this->getJson('/api/v1/categories')
            ->assertOk()
            ->assertJsonFragment(['slug' => 'homme']);
    }

    public function test_deleting_a_category_with_products_is_blocked(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id]);

        $this->actingAs($this->admin(), 'api')
            ->deleteJson("/api/v1/admin/categories/{$category->id}")
            ->assertStatus(422);
    }

    public function test_deleting_a_category_with_children_is_blocked(): void
    {
        $parent = Category::factory()->create();
        Category::factory()->create(['parent_id' => $parent->id]);

        $this->actingAs($this->admin(), 'api')
            ->deleteJson("/api/v1/admin/categories/{$parent->id}")
            ->assertStatus(422);
    }

    public function test_deleting_an_unused_category_succeeds(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->admin(), 'api')
            ->deleteJson("/api/v1/admin/categories/{$category->id}")
            ->assertOk();

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
}

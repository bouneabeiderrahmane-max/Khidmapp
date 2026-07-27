<?php

namespace Tests\Feature\Catalog;

use App\Models\Boutique;
use App\Models\Category;
use App\Models\CategoryMapping;
use App\Models\User;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryMappingTest extends TestCase
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

    public function test_it_lists_mappings_for_a_boutique(): void
    {
        $boutique = Boutique::factory()->create();
        CategoryMapping::query()->create([
            'boutique_id' => $boutique->id,
            'source_category_ref' => 'ropa-mujer',
        ]);

        $this->actingAs($this->admin, 'api')
            ->getJson("/api/v1/admin/boutiques/{$boutique->id}/category-mappings")
            ->assertOk()
            ->assertJsonFragment(['source_category_ref' => 'ropa-mujer', 'category' => null]);
    }

    public function test_it_assigns_a_unified_category_to_a_mapping(): void
    {
        $boutique = Boutique::factory()->create();
        $category = Category::factory()->create();
        $mapping = CategoryMapping::query()->create([
            'boutique_id' => $boutique->id,
            'source_category_ref' => 'ropa-mujer',
        ]);

        $this->actingAs($this->admin, 'api')
            ->putJson("/api/v1/admin/boutiques/{$boutique->id}/category-mappings/{$mapping->id}", [
                'category_id' => $category->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.category.id', $category->id);
    }

    public function test_a_mapping_from_another_boutique_returns_404(): void
    {
        $boutiqueA = Boutique::factory()->create();
        $boutiqueB = Boutique::factory()->create();
        $mapping = CategoryMapping::query()->create([
            'boutique_id' => $boutiqueA->id,
            'source_category_ref' => 'ropa-mujer',
        ]);

        $this->actingAs($this->admin, 'api')
            ->putJson("/api/v1/admin/boutiques/{$boutiqueB->id}/category-mappings/{$mapping->id}", ['category_id' => null])
            ->assertStatus(404);
    }
}

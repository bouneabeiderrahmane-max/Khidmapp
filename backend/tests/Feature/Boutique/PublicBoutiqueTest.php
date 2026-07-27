<?php

namespace Tests\Feature\Boutique;

use App\Models\Boutique;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicBoutiqueTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_public_catalog_only_lists_active_boutiques(): void
    {
        Boutique::factory()->active()->create(['name' => 'Zara España']);
        Boutique::factory()->create(['name' => 'En test shop']); // en_test by default
        Boutique::factory()->create(['name' => 'Inactive shop', 'status' => 'inactive']);

        $response = $this->getJson('/api/v1/boutiques');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name');
        $this->assertEquals(['Zara España'], $names->all());
    }

    public function test_showing_a_non_active_boutique_returns_404(): void
    {
        $boutique = Boutique::factory()->create(['status' => 'en_test']);

        $this->getJson("/api/v1/boutiques/{$boutique->slug}")->assertStatus(404);
    }

    public function test_showing_an_active_boutique_by_slug_succeeds(): void
    {
        $boutique = Boutique::factory()->active()->create(['name' => 'Bershka']);

        $this->getJson("/api/v1/boutiques/{$boutique->slug}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Bershka');
    }

    public function test_the_public_response_hides_margin_and_sync_config(): void
    {
        $boutique = Boutique::factory()->active()->create([
            'default_margin_percent' => 25,
            'sync_config' => ['frequency_hours' => 6],
        ]);

        $response = $this->getJson("/api/v1/boutiques/{$boutique->slug}");

        $response->assertOk();
        $this->assertArrayNotHasKey('default_margin_percent', $response->json('data'));
        $this->assertArrayNotHasKey('sync_config', $response->json('data'));
    }
}

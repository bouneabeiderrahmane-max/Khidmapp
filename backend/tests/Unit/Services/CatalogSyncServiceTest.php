<?php

namespace Tests\Unit\Services;

use App\DataTransferObjects\Catalog\RawCatalogProduct;
use App\DataTransferObjects\Catalog\RawCatalogVariant;
use App\Models\Boutique;
use App\Models\Product;
use App\Services\Catalog\CatalogSyncService;
use App\Services\Catalog\PassthroughTranslator;
use App\Support\ProductStatus;
use App\Support\SyncLogStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\Support\FakeCatalogFetcher;
use Tests\TestCase;

class CatalogSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    private FakeCatalogFetcher $fetcher;

    private CatalogSyncService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fetcher = new FakeCatalogFetcher;
        $this->service = new CatalogSyncService($this->fetcher, new PassthroughTranslator);
    }

    private function rawProduct(string $ref, string $categoryRef = 'ropa-mujer', float $price = 19.99): RawCatalogProduct
    {
        return new RawCatalogProduct(
            externalRef: $ref,
            name: "Produit $ref",
            description: 'Description',
            images: ['https://example.com/img.jpg'],
            sourceCategoryRef: $categoryRef,
            sourceUrl: 'https://example.com/'.$ref,
            variants: [
                new RawCatalogVariant("$ref-s", "$ref-S", 'S', null, $price),
            ],
        );
    }

    public function test_sync_creates_products_variants_and_category_mappings(): void
    {
        $boutique = Boutique::factory()->create();
        $this->fetcher->willReturn([$this->rawProduct('p1'), $this->rawProduct('p2')]);

        $log = $this->service->sync($boutique);

        $this->assertSame(SyncLogStatus::SUCCESS, $log->status);
        $this->assertSame(2, $log->products_created);
        $this->assertSame(0, $log->products_updated);
        $this->assertSame(2, Product::query()->where('boutique_id', $boutique->id)->count());
        $this->assertDatabaseHas('category_mappings', [
            'boutique_id' => $boutique->id,
            'source_category_ref' => 'ropa-mujer',
        ]);
        $product = Product::query()->where('external_ref', 'p1')->firstOrFail();
        $this->assertCount(1, $product->variants);
    }

    public function test_sync_updates_an_existing_product_instead_of_duplicating(): void
    {
        $boutique = Boutique::factory()->create();
        $this->fetcher->willReturn([$this->rawProduct('p1', price: 10)]);
        $this->service->sync($boutique);

        $this->fetcher->willReturn([$this->rawProduct('p1', price: 15)]);
        $log = $this->service->sync($boutique);

        $this->assertSame(0, $log->products_created);
        $this->assertSame(1, $log->products_updated);
        $this->assertSame(1, Product::query()->where('boutique_id', $boutique->id)->count());
        $this->assertEquals(15, Product::query()->where('external_ref', 'p1')->first()->base_price_eur);
    }

    public function test_a_product_missing_from_the_next_cycle_is_marked_unavailable(): void
    {
        $boutique = Boutique::factory()->create();
        $this->fetcher->willReturn([$this->rawProduct('p1'), $this->rawProduct('p2')]);
        $this->service->sync($boutique);

        $this->fetcher->willReturn([$this->rawProduct('p1')]);
        $log = $this->service->sync($boutique);

        $this->assertSame(1, $log->products_disabled);
        $p2 = Product::query()->where('external_ref', 'p2')->first();
        $this->assertSame(ProductStatus::INDISPONIBLE, $p2->status);
        $this->assertNotNull($p2->unavailable_since);
    }

    public function test_a_product_unavailable_past_the_grace_period_is_discontinued(): void
    {
        $boutique = Boutique::factory()->create([
            'sync_config' => ['unavailable_grace_days' => 7],
        ]);
        Product::factory()->create([
            'boutique_id' => $boutique->id,
            'external_ref' => 'ghost',
            'status' => ProductStatus::INDISPONIBLE,
            'unavailable_since' => now()->subDays(10),
        ]);
        $this->fetcher->willReturn([]);

        $this->service->sync($boutique);

        $this->assertSame(ProductStatus::DISCONTINUED, Product::query()->where('external_ref', 'ghost')->first()->status);
    }

    public function test_translation_locked_products_are_not_overwritten_on_resync(): void
    {
        $boutique = Boutique::factory()->create();
        $this->fetcher->willReturn([$this->rawProduct('p1')]);
        $this->service->sync($boutique);

        $product = Product::query()->where('external_ref', 'p1')->firstOrFail();
        $product->update(['name' => ['fr' => 'Corrigé', 'ar' => 'مصحح'], 'translation_locked' => true]);

        $this->service->sync($boutique);

        $product->refresh();
        $this->assertSame('Corrigé', $product->name['fr']);
    }

    public function test_a_fetch_failure_preserves_the_existing_catalog_and_logs_the_error(): void
    {
        $boutique = Boutique::factory()->create();
        $this->fetcher->willReturn([$this->rawProduct('p1')]);
        $this->service->sync($boutique);
        $this->assertSame(1, Product::query()->where('boutique_id', $boutique->id)->count());

        $this->fetcher->willThrow(new RuntimeException('Site injoignable'));
        $log = $this->service->sync($boutique);

        $this->assertSame(SyncLogStatus::FAILED, $log->status);
        $this->assertSame(['Site injoignable'], $log->errors);
        $this->assertSame(1, Product::query()->where('boutique_id', $boutique->id)->count());
        $this->assertSame(ProductStatus::ACTIVE, Product::query()->where('external_ref', 'p1')->first()->status);
    }
}

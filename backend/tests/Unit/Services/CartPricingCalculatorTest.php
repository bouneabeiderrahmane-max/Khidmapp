<?php

namespace Tests\Unit\Services;

use App\Models\Boutique;
use App\Models\CartItem;
use App\Models\DeliveryFeeTier;
use App\Models\ExchangeRate;
use App\Models\MarginRule;
use App\Models\Product;
use App\Services\Pricing\CartPricingCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Garde-fou contre la régression "frais de livraison par ligne" : le CDC
 * (8.6) consolide les articles d'une même commande en un seul colis, donc
 * les frais de livraison doivent être calculés une seule fois sur le
 * sous-total agrégé, pas sommés ligne par ligne.
 */
class CartPricingCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private CartPricingCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = app(CartPricingCalculator::class);

        ExchangeRate::query()->create(['currency_pair' => 'EUR_MRU', 'rate' => 10, 'effective_at' => now()->subDay()]);
        MarginRule::query()->create(['scope_type' => 'global', 'scope_id' => null, 'percent' => 0, 'effective_at' => now()->subDay()]);
        DeliveryFeeTier::query()->create(['zone' => 'nouakchott', 'min_price_mru' => 0, 'max_price_mru' => 1500, 'fee_mru' => 200]);
        DeliveryFeeTier::query()->create(['zone' => 'nouakchott', 'min_price_mru' => 1500, 'max_price_mru' => null, 'fee_mru' => 400]);
    }

    private function cartItem(float $priceEur, int $quantity): CartItem
    {
        $product = Product::factory()->create(['boutique_id' => Boutique::factory(), 'base_price_eur' => $priceEur]);
        $variant = $product->variants()->create(['external_variant_ref' => 'v-'.$product->id, 'price_eur' => $priceEur, 'stock_status' => 'in_stock']);

        $item = new CartItem(['quantity' => $quantity]);
        $item->setRelation('variant', $variant);

        return $item;
    }

    public function test_delivery_fee_is_charged_once_on_the_aggregated_subtotal_not_per_line(): void
    {
        // Deux lignes à 70 € (=> 700 MRU chacune) : une somme naïve par
        // ligne facturerait 200 + 200 = 400 MRU (chaque ligne prise
        // isolément reste sous le palier à 1500 MRU). Consolidées, les
        // 1400 MRU restent aussi sous ce palier => un seul frais de 200 MRU,
        // pas 400 : c'est le comportement attendu (8.6).
        $items = new Collection([$this->cartItem(70, 1), $this->cartItem(70, 1)]);

        $totals = $this->calculator->calculate($items, 'nouakchott');

        $this->assertSame(1400.0, $totals->subtotalMru);
        $this->assertSame(200.0, $totals->deliveryFeeMru);
        $this->assertSame(1600.0, $totals->totalMru);
    }

    public function test_a_consolidated_subtotal_can_cross_into_a_higher_tier(): void
    {
        // Deux lignes à 100 € (=> 1000 MRU chacune) restent chacune sous le
        // palier à 1500 MRU si on les évalue isolément, mais leur somme
        // consolidée (2000 MRU) franchit le second palier (400 MRU).
        $items = new Collection([$this->cartItem(100, 1), $this->cartItem(100, 1)]);

        $totals = $this->calculator->calculate($items, 'nouakchott');

        $this->assertSame(2000.0, $totals->subtotalMru);
        $this->assertSame(400.0, $totals->deliveryFeeMru);
    }

    public function test_a_single_low_value_item_stays_in_the_first_tier(): void
    {
        $items = new Collection([$this->cartItem(50, 1)]);

        $totals = $this->calculator->calculate($items, 'nouakchott');

        $this->assertSame(500.0, $totals->subtotalMru);
        $this->assertSame(200.0, $totals->deliveryFeeMru);
    }

    public function test_quantity_multiplies_the_line_subtotal(): void
    {
        $items = new Collection([$this->cartItem(50, 3)]);

        $totals = $this->calculator->calculate($items, 'nouakchott');

        $this->assertSame(1500.0, $totals->subtotalMru);
        $this->assertSame(1500.0, $totals->lines[0]['line_subtotal_mru']);
    }

    public function test_an_empty_cart_has_no_delivery_fee(): void
    {
        $totals = $this->calculator->calculate(new Collection, 'nouakchott');

        $this->assertSame(0.0, $totals->subtotalMru);
        $this->assertSame(0.0, $totals->deliveryFeeMru);
        $this->assertSame(0.0, $totals->totalMru);
    }
}

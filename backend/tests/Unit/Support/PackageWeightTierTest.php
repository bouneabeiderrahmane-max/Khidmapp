<?php

namespace Tests\Unit\Support;

use App\Support\PackageWeightTier;
use PHPUnit\Framework\TestCase;

class PackageWeightTierTest extends TestCase
{
    public function test_base_fees_match_the_amounts_provided_by_the_user(): void
    {
        $this->assertSame(600.0, PackageWeightTier::feeMru(PackageWeightTier::PETIT));
        $this->assertSame(1200.0, PackageWeightTier::feeMru(PackageWeightTier::MOYEN));
        $this->assertSame(1700.0, PackageWeightTier::feeMru(PackageWeightTier::TRES_GRAND));
    }

    public function test_extra_weight_beyond_15kg_is_charged_200_mru_per_kg_on_the_largest_tier(): void
    {
        $this->assertSame(1900.0, PackageWeightTier::feeMru(PackageWeightTier::TRES_GRAND, 1.0));
        $this->assertSame(2700.0, PackageWeightTier::feeMru(PackageWeightTier::TRES_GRAND, 5.0));
    }

    public function test_only_the_largest_tier_accepts_extra_weight(): void
    {
        $this->assertTrue(PackageWeightTier::acceptsExtraWeight(PackageWeightTier::TRES_GRAND));
        $this->assertFalse(PackageWeightTier::acceptsExtraWeight(PackageWeightTier::PETIT));
        $this->assertFalse(PackageWeightTier::acceptsExtraWeight(PackageWeightTier::MOYEN));
    }

    public function test_extra_weight_is_ignored_for_tiers_that_do_not_accept_it(): void
    {
        $this->assertSame(600.0, PackageWeightTier::feeMru(PackageWeightTier::PETIT, 3.0));
    }

    public function test_it_rejects_an_unknown_tier(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PackageWeightTier::feeMru('inconnu');
    }
}

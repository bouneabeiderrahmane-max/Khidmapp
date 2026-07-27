<?php

namespace App\DataTransferObjects\Catalog;

readonly class RawCatalogVariant
{
    public function __construct(
        public string $externalVariantRef,
        public ?string $sku,
        public ?string $size,
        public ?string $color,
        public float $priceEur,
        public bool $inStock = true,
    ) {}
}

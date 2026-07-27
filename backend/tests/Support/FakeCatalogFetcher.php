<?php

namespace Tests\Support;

use App\Contracts\CatalogFetcher;
use App\DataTransferObjects\Catalog\RawCatalogProduct;
use App\Models\Boutique;
use Throwable;

class FakeCatalogFetcher implements CatalogFetcher
{
    /** @var array<int, RawCatalogProduct> */
    private array $products = [];

    private ?Throwable $throws = null;

    public function willReturn(array $products): static
    {
        $this->products = $products;

        return $this;
    }

    public function willThrow(Throwable $e): static
    {
        $this->throws = $e;

        return $this;
    }

    public function fetch(Boutique $boutique): iterable
    {
        if ($this->throws !== null) {
            throw $this->throws;
        }

        return $this->products;
    }
}

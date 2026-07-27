<?php

namespace App\Http\Controllers;

use App\Http\Requests\Product\SearchProductsRequest;
use App\Http\Resources\PublicProductDetailResource;
use App\Http\Resources\PublicProductResource;
use App\Models\Product;
use App\Services\Catalog\ProductSearchService;
use App\Services\Pricing\PricingService;
use App\Support\ProductStatus;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductSearchService $search,
        private readonly PricingService $pricing,
    ) {}

    public function index(SearchProductsRequest $request): AnonymousResourceCollection
    {
        $products = $this->search->search($request->validated());

        return PublicProductResource::collection($products);
    }

    public function show(Product $product): PublicProductDetailResource
    {
        abort_unless(
            $product->status === ProductStatus::ACTIVE && $product->boutique->status === 'active',
            404
        );

        $product->load(['boutique', 'category', 'variants']);

        foreach ($product->variants as $variant) {
            $variant->setAttribute('price_mru', $this->pricing->priceForVariant($variant)->finalPriceMru);
        }

        return new PublicProductDetailResource($product);
    }
}

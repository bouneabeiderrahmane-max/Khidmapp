<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $products = Product::query()
            ->with(['category', 'variants'])
            ->when($request->filled('boutique_id'), fn ($q) => $q->where('boutique_id', $request->integer('boutique_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest('last_synced_at')
            ->paginate($request->integer('per_page', 20));

        return ProductResource::collection($products);
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource($product->load(['category', 'variants']));
    }

    /**
     * Correction manuelle de la traduction automatique et de la catégorie
     * (8.2.1) — ne touche pas au prix ni aux variantes, alimentés par la
     * synchronisation.
     */
    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $data = $request->validated();

        // Toute correction manuelle du nom ou de la description gèle la
        // traduction : les synchronisations suivantes ne l'écraseront plus
        // (voir CatalogSyncService::upsertProduct).
        if (array_key_exists('name', $data) || array_key_exists('description', $data)) {
            $data['translation_locked'] = true;
        }

        $product->update($data);

        return new ProductResource($product->fresh(['category', 'variants']));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryMapping\UpdateCategoryMappingRequest;
use App\Http\Resources\CategoryMappingResource;
use App\Models\Boutique;
use App\Models\CategoryMapping;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryMappingController extends Controller
{
    /**
     * Liste des catégories source rencontrées pour cette boutique lors des
     * synchronisations, mappées ou non vers une catégorie unifiée (8.2.1).
     */
    public function index(Boutique $boutique): AnonymousResourceCollection
    {
        $mappings = $boutique->categoryMappings()->with('category')->orderBy('source_category_ref')->get();

        return CategoryMappingResource::collection($mappings);
    }

    public function update(UpdateCategoryMappingRequest $request, Boutique $boutique, CategoryMapping $categoryMapping): CategoryMappingResource
    {
        abort_if($categoryMapping->boutique_id !== $boutique->id, 404);

        $categoryMapping->update($request->validated());

        return new CategoryMappingResource($categoryMapping->fresh('category'));
    }
}

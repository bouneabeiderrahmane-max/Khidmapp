<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return CategoryResource::collection(Category::query()->orderBy('slug')->get());
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::query()->create($request->validated());

        return (new CategoryResource($category))->response()->setStatusCode(201);
    }

    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource
    {
        $category->update($request->validated());

        return new CategoryResource($category->fresh());
    }

    public function destroy(Category $category): JsonResponse
    {
        abort_if($category->children()->exists(), 422, __('khidmapp.category_has_children'));
        abort_if($category->products()->exists(), 422, __('khidmapp.category_has_products'));

        $category->delete();

        return response()->json(['message' => 'ok']);
    }
}

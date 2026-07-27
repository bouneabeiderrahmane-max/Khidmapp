<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pricing\StoreMarginRuleRequest;
use App\Http\Resources\MarginRuleResource;
use App\Models\MarginRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MarginRuleController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $rules = MarginRule::query()
            ->when($request->filled('scope_type'), fn ($q) => $q->where('scope_type', $request->string('scope_type')))
            ->orderByDesc('effective_at')
            ->paginate(20);

        return MarginRuleResource::collection($rules);
    }

    public function store(StoreMarginRuleRequest $request): JsonResponse
    {
        $rule = MarginRule::query()->create([
            'scope_type' => $request->input('scope_type'),
            'scope_id' => $request->input('scope_id'),
            'percent' => $request->input('percent'),
            'effective_at' => $request->input('effective_at', now()),
            'created_by' => $request->user()->id,
        ]);

        return (new MarginRuleResource($rule))->response()->setStatusCode(201);
    }
}

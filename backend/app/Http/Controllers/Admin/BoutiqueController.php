<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Boutique\StoreBoutiqueRequest;
use App\Http\Requests\Boutique\UpdateBoutiqueRequest;
use App\Http\Requests\Boutique\UpdateBoutiqueStatusRequest;
use App\Http\Resources\BoutiqueResource;
use App\Models\Boutique;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BoutiqueController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $boutiques = Boutique::query()->orderBy('name')->paginate(
            $request->integer('per_page', 20)
        );

        return BoutiqueResource::collection($boutiques);
    }

    public function store(StoreBoutiqueRequest $request): JsonResponse
    {
        $boutique = Boutique::query()->create($request->validated());

        // Refresh: DB-level defaults (e.g. status) applied by PostgreSQL at
        // insert time aren't reflected on the in-memory instance otherwise.
        return (new BoutiqueResource($boutique->fresh()))->response()->setStatusCode(201);
    }

    public function show(Boutique $boutique): BoutiqueResource
    {
        return new BoutiqueResource($boutique);
    }

    public function update(UpdateBoutiqueRequest $request, Boutique $boutique): BoutiqueResource
    {
        $boutique->update($request->validated());

        return new BoutiqueResource($boutique->fresh());
    }

    /**
     * Transition de statut dédiée : Active / Inactive / En pause / En test
     * (8.1). Une boutique désactivée reste en base (ses commandes en cours
     * sont menées à terme, 8.1.2) — seul son statut change, jamais une
     * suppression.
     */
    public function updateStatus(UpdateBoutiqueStatusRequest $request, Boutique $boutique): BoutiqueResource
    {
        $boutique->update(['status' => $request->validated('status')]);

        return new BoutiqueResource($boutique->fresh());
    }

    public function destroy(Boutique $boutique): JsonResponse
    {
        abort_unless($boutique->canBeDeleted(), 422, __('khidmapp.boutique_deletion_blocked'));

        $boutique->delete();

        return response()->json(['message' => 'ok']);
    }
}

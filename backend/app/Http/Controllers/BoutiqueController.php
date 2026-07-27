<?php

namespace App\Http\Controllers;

use App\Http\Resources\BoutiqueResource;
use App\Models\Boutique;
use App\Support\BoutiqueStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BoutiqueController extends Controller
{
    /**
     * Catalogue public des boutiques actives uniquement (règle 8.1.2 : une
     * boutique désactivée/en pause/en test n'apparaît plus au client).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $boutiques = Boutique::query()->active()->orderBy('name')->paginate(
            $request->integer('per_page', 20)
        );

        return BoutiqueResource::collection($boutiques);
    }

    public function show(Boutique $boutique): BoutiqueResource
    {
        abort_unless($boutique->status === BoutiqueStatus::ACTIVE, 404);

        return new BoutiqueResource($boutique);
    }
}

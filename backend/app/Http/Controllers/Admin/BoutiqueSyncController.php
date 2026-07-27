<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SyncLogResource;
use App\Jobs\SyncBoutiqueCatalog;
use App\Models\Boutique;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BoutiqueSyncController extends Controller
{
    /**
     * Déclenche une synchronisation immédiate (mise en file d'attente Redis).
     */
    public function trigger(Boutique $boutique): JsonResponse
    {
        SyncBoutiqueCatalog::dispatch($boutique->id);

        return response()->json(['message' => 'ok'], 202);
    }

    /**
     * Journal de synchronisation (8.2.3) : consultable par l'administrateur.
     */
    public function logs(Request $request, Boutique $boutique): AnonymousResourceCollection
    {
        $logs = $boutique->syncLogs()
            ->latest('started_at')
            ->paginate($request->integer('per_page', 20));

        return SyncLogResource::collection($logs);
    }
}

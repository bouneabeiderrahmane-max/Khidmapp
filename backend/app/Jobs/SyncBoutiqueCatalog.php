<?php

namespace App\Jobs;

use App\Models\Boutique;
use App\Services\Catalog\CatalogSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncBoutiqueCatalog implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public readonly int $boutiqueId) {}

    public function handle(CatalogSyncService $service): void
    {
        $boutique = Boutique::query()->find($this->boutiqueId);

        if ($boutique === null) {
            return;
        }

        $service->sync($boutique);
    }
}

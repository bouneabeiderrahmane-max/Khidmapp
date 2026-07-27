<?php

namespace App\Console\Commands;

use App\Jobs\SyncBoutiqueCatalog;
use App\Models\Boutique;
use Illuminate\Console\Command;

class SyncCatalogs extends Command
{
    protected $signature = 'catalog:sync {boutique? : ID ou slug de la boutique à synchroniser (toutes si omis)}';

    protected $description = 'Déclenche la synchronisation catalogue (8.2) pour une boutique ou toutes les boutiques actives/en test';

    public function handle(): int
    {
        $identifier = $this->argument('boutique');

        $boutiques = $identifier
            ? Boutique::query()->where('id', $identifier)->orWhere('slug', $identifier)->get()
            : Boutique::query()->syncable()->get();

        if ($boutiques->isEmpty()) {
            $this->error('Aucune boutique à synchroniser.');

            return self::FAILURE;
        }

        foreach ($boutiques as $boutique) {
            SyncBoutiqueCatalog::dispatch($boutique->id);
            $this->info("Synchronisation mise en file d'attente : {$boutique->name}");
        }

        return self::SUCCESS;
    }
}

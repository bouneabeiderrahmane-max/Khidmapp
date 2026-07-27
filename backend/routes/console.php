<?php

use App\Jobs\SyncBoutiqueCatalog;
use App\Models\Boutique;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Chaque boutique a sa propre fréquence de synchronisation (8.2.1,
// sync_config.frequency_hours) : on vérifie toutes les heures quelles
// boutiques sont dues plutôt que de figer un planning global.
Schedule::call(function () {
    Boutique::query()->syncable()->get()->each(function (Boutique $boutique) {
        $frequencyHours = $boutique->sync_config['frequency_hours'] ?? 24;
        $lastSync = $boutique->syncLogs()->latest('started_at')->first();

        if ($lastSync === null || $lastSync->started_at->addHours($frequencyHours)->isPast()) {
            SyncBoutiqueCatalog::dispatch($boutique->id);
        }
    });
})->hourly()->name('catalog-sync-scheduler')->withoutOverlapping();

<?php

namespace Database\Seeders;

use App\Models\Boutique;
use App\Support\BoutiqueStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Convenance de développement/test local — n'a rien à voir avec
 * `BoutiqueSeeder` (les 8 boutiques de référence pour le lancement, CDC
 * 8.1.1), qui les laisse volontairement au statut "en_test" tant qu'aucune
 * vraie synchronisation catalogue n'existe (voir son docblock). Cette
 * limite bloque tout test manuel du catalogue public ou du "pedido
 * personalizado" sur une base fraîchement seedée : `BoutiqueController::
 * index()` (endpoint public `/boutiques`) ne renvoie que les boutiques
 * `active`.
 *
 * Ce seeder force donc un jeu de boutiques réelles et reconnaissables
 * (noms/URL exacts) au statut `active`, y compris en re-promouvant celles
 * déjà créées par `BoutiqueSeeder` (mêmes slugs) — d'où `updateOrCreate`
 * plutôt que `firstOrCreate`, qui laisserait une boutique déjà existante
 * bloquée en "en_test".
 *
 * Volontairement PAS appelé depuis `DatabaseSeeder` : à lancer à la
 * demande, uniquement en environnement de développement/test —
 * `php artisan db:seed --class=DemoBoutiqueSeeder`.
 */
class DemoBoutiqueSeeder extends Seeder
{
    private const DEMO_BOUTIQUES = [
        ['name' => 'Zara España', 'base_url' => 'https://www.zara.com/es/'],
        ['name' => 'Amazon.es', 'base_url' => 'https://www.amazon.es/'],
        ['name' => 'Decathlon España', 'base_url' => 'https://www.decathlon.es/'],
        ['name' => 'El Corte Inglés', 'base_url' => 'https://www.elcorteingles.es/'],
        ['name' => 'Mango España', 'base_url' => 'https://shop.mango.com/es'],
        ['name' => 'Bershka', 'base_url' => 'https://www.bershka.com/es/'],
        ['name' => 'Nike España', 'base_url' => 'https://www.nike.com/es/'],
        ['name' => 'MediaMarkt España', 'base_url' => 'https://www.mediamarkt.es/'],
    ];

    public function run(): void
    {
        foreach (self::DEMO_BOUTIQUES as $boutique) {
            Boutique::query()->updateOrCreate(
                ['slug' => Str::slug($boutique['name'])],
                [
                    'name' => $boutique['name'],
                    'base_url' => $boutique['base_url'],
                    'country_code' => 'ES',
                    'currency_code' => 'EUR',
                    'status' => BoutiqueStatus::ACTIVE,
                    'sync_config' => [
                        'frequency_hours' => 24,
                        'included_categories' => [],
                        'excluded_categories' => [],
                        'translation_auto' => true,
                        'alert_threshold_percent' => 10,
                        'unavailable_grace_days' => 14,
                    ],
                ]
            );
        }
    }
}

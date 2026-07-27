<?php

namespace Database\Seeders;

use App\Models\Boutique;
use App\Support\BoutiqueStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BoutiqueSeeder extends Seeder
{
    /**
     * Boutiques de référence pour le lancement (cahier des charges, 8.1.1).
     * Statut "en_test" par défaut : la synchronisation réelle par site n'est
     * pas encore implémentée (StubCatalogFetcher est un placeholder — voir
     * son docblock), donc aucune boutique n'est publiable au catalogue
     * client pour l'instant (règle 8.1.2 : le statut "en_test" sert
     * justement à valider la qualité de la synchronisation avant
     * publication).
     */
    private const REFERENCE_BOUTIQUES = [
        ['name' => 'Zara España', 'base_url' => 'https://www.zara.com/es/'],
        ['name' => 'Mango España', 'base_url' => 'https://shop.mango.com/es'],
        ['name' => 'Bershka', 'base_url' => 'https://www.bershka.com/es/'],
        ['name' => 'Pull & Bear', 'base_url' => 'https://www.pullandbear.com/es/'],
        ['name' => 'Stradivarius', 'base_url' => 'https://www.stradivarius.com/es/'],
        ['name' => 'Primor', 'base_url' => 'https://www.primor.eu/'],
        ['name' => 'El Corte Inglés', 'base_url' => 'https://www.elcorteingles.es/'],
        ['name' => 'Nike España', 'base_url' => 'https://www.nike.com/es/'],
    ];

    public function run(): void
    {
        foreach (self::REFERENCE_BOUTIQUES as $boutique) {
            Boutique::query()->firstOrCreate(
                ['slug' => Str::slug($boutique['name'])],
                [
                    'name' => $boutique['name'],
                    'base_url' => $boutique['base_url'],
                    'country_code' => 'ES',
                    'currency_code' => 'EUR',
                    'status' => BoutiqueStatus::EN_TEST,
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

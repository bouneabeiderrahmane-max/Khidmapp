<?php

namespace Database\Seeders;

use App\Models\Boutique;
use App\Models\Category;
use App\Models\Product;
use App\Support\ProductStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Convenance de développement/test local, au même titre que
 * `DemoBoutiqueSeeder` (dont ce seeder dépend — les boutiques référencées
 * ci-dessous doivent déjà exister). Aucun vrai `CatalogFetcher` n'existe
 * encore (voir son docblock, Sprint 3) : sans ce seeder, les 8 boutiques
 * actives de `DemoBoutiqueSeeder` n'ont aucun produit, et le catalogue
 * (tape sur une boutique depuis l'accueil mobile) reste vide même une
 * fois les boutiques visibles.
 *
 * Volontairement PAS appelé depuis `DatabaseSeeder` — à lancer à la
 * demande : `php artisan db:seed --class=DemoProductSeeder`.
 * Comme pour les boutiques (Sprint 3), aucune image produit n'est
 * fournie ici : l'app affiche déjà proprement un espace réservé.
 */
class DemoProductSeeder extends Seeder
{
    private const CATEGORIES = [
        'vetements' => ['fr' => 'Vêtements', 'ar' => 'الملابس'],
        'chaussures-sport' => ['fr' => 'Chaussures & Sport', 'ar' => 'الأحذية والرياضة'],
        'maison-bricolage' => ['fr' => 'Maison & Bricolage', 'ar' => 'المنزل والأدوات'],
        'beaute-parfumerie' => ['fr' => 'Beauté & Parfumerie', 'ar' => 'الجمال والعطور'],
        'electronique' => ['fr' => 'Électronique', 'ar' => 'الإلكترونيات'],
    ];

    /**
     * boutique_slug => liste de produits.
     * Chaque produit : nom bilingue, catégorie, prix de base, variantes
     * (taille/couleur + prix, comme le ferait une vraie synchronisation).
     */
    private const PRODUCTS_BY_BOUTIQUE = [
        'zara-espana' => [
            [
                'ref' => 'zara-tshirt-basique',
                'name' => ['fr' => 'T-shirt basique coton', 'ar' => 'تي شيرت أساسي من القطن'],
                'category' => 'vetements',
                'variants' => [
                    ['ref' => 'v1', 'size' => 'S', 'color' => 'Blanc', 'price_eur' => 12.95],
                    ['ref' => 'v2', 'size' => 'M', 'color' => 'Noir', 'price_eur' => 12.95],
                ],
            ],
            [
                'ref' => 'zara-jean-slim',
                'name' => ['fr' => 'Jean slim', 'ar' => 'جينز ضيق'],
                'category' => 'vetements',
                'variants' => [
                    ['ref' => 'v1', 'size' => '38', 'color' => 'Bleu', 'price_eur' => 29.95],
                    ['ref' => 'v2', 'size' => '40', 'color' => 'Bleu', 'price_eur' => 29.95],
                ],
            ],
        ],
        'mango-espana' => [
            [
                'ref' => 'mango-robe-imprimee',
                'name' => ['fr' => 'Robe imprimée', 'ar' => 'فستان مطبوع'],
                'category' => 'vetements',
                'variants' => [
                    ['ref' => 'v1', 'size' => 'S', 'color' => 'Floral', 'price_eur' => 39.99],
                    ['ref' => 'v2', 'size' => 'M', 'color' => 'Floral', 'price_eur' => 39.99],
                ],
            ],
            [
                'ref' => 'mango-blazer',
                'name' => ['fr' => 'Blazer structuré', 'ar' => 'بليزر منسق'],
                'category' => 'vetements',
                'variants' => [
                    ['ref' => 'v1', 'size' => '36', 'color' => 'Beige', 'price_eur' => 59.99],
                    ['ref' => 'v2', 'size' => '38', 'color' => 'Beige', 'price_eur' => 59.99],
                ],
            ],
        ],
        'bershka' => [
            [
                'ref' => 'bershka-sweat-capuche',
                'name' => ['fr' => 'Sweat à capuche oversize', 'ar' => 'هودي واسع'],
                'category' => 'vetements',
                'variants' => [
                    ['ref' => 'v1', 'size' => 'M', 'color' => 'Gris', 'price_eur' => 25.99],
                    ['ref' => 'v2', 'size' => 'L', 'color' => 'Gris', 'price_eur' => 25.99],
                ],
            ],
            [
                'ref' => 'bershka-baskets',
                'name' => ['fr' => 'Baskets running', 'ar' => 'حذاء رياضي للجري'],
                'category' => 'chaussures-sport',
                'variants' => [
                    ['ref' => 'v1', 'size' => '40', 'color' => 'Blanc', 'price_eur' => 34.99],
                    ['ref' => 'v2', 'size' => '42', 'color' => 'Blanc', 'price_eur' => 34.99],
                ],
            ],
        ],
        'nike-espana' => [
            [
                'ref' => 'nike-air-max',
                'name' => ['fr' => 'Nike Air Max', 'ar' => 'نايكي إير ماكس'],
                'category' => 'chaussures-sport',
                'variants' => [
                    ['ref' => 'v1', 'size' => '42', 'color' => 'Noir', 'price_eur' => 129.99],
                    ['ref' => 'v2', 'size' => '44', 'color' => 'Noir', 'price_eur' => 129.99],
                ],
            ],
            [
                'ref' => 'nike-short-drifit',
                'name' => ['fr' => 'Short de sport Dri-FIT', 'ar' => 'شورت رياضي دراي فيت'],
                'category' => 'chaussures-sport',
                'variants' => [
                    ['ref' => 'v1', 'size' => 'M', 'color' => 'Noir', 'price_eur' => 24.99],
                    ['ref' => 'v2', 'size' => 'L', 'color' => 'Noir', 'price_eur' => 24.99],
                ],
            ],
        ],
        'el-corte-ingles' => [
            [
                'ref' => 'eci-set-casseroles',
                'name' => ['fr' => 'Set de casseroles inox (5 pièces)', 'ar' => 'طقم قدور ستانلس (5 قطع)'],
                'category' => 'maison-bricolage',
                'variants' => [
                    ['ref' => 'v1', 'size' => null, 'color' => 'Inox', 'price_eur' => 79.99],
                ],
            ],
            [
                'ref' => 'eci-eau-toilette',
                'name' => ['fr' => 'Eau de toilette homme 100 ml', 'ar' => 'عطر رجالي 100 مل'],
                'category' => 'beaute-parfumerie',
                'variants' => [
                    ['ref' => 'v1', 'size' => '100ml', 'color' => null, 'price_eur' => 65.00],
                ],
            ],
        ],
        'amazones' => [
            [
                'ref' => 'amazon-echo-dot',
                'name' => ['fr' => 'Echo Dot (5ᵉ génération)', 'ar' => 'إيكو دوت (الجيل الخامس)'],
                'category' => 'electronique',
                'variants' => [
                    ['ref' => 'v1', 'size' => null, 'color' => 'Noir', 'price_eur' => 59.99],
                ],
            ],
            [
                'ref' => 'amazon-kindle-paperwhite',
                'name' => ['fr' => 'Kindle Paperwhite', 'ar' => 'كيندل بيبروايت'],
                'category' => 'electronique',
                'variants' => [
                    ['ref' => 'v1', 'size' => null, 'color' => 'Noir', 'price_eur' => 139.99],
                ],
            ],
        ],
        'decathlon-espana' => [
            [
                'ref' => 'decathlon-vtt-rockrider',
                'name' => ['fr' => 'VTT Rockrider ST100', 'ar' => 'دراجة جبلية روكرايدر ST100'],
                'category' => 'chaussures-sport',
                'variants' => [
                    ['ref' => 'v1', 'size' => 'M', 'color' => 'Vert', 'price_eur' => 299.99],
                ],
            ],
            [
                'ref' => 'decathlon-tente-camping',
                'name' => ['fr' => 'Tente de camping 2 places', 'ar' => 'خيمة تخييم لشخصين'],
                'category' => 'chaussures-sport',
                'variants' => [
                    ['ref' => 'v1', 'size' => null, 'color' => null, 'price_eur' => 89.99],
                ],
            ],
        ],
        'mediamarkt-espana' => [
            [
                'ref' => 'mediamarkt-casque-audio',
                'name' => ['fr' => 'Casque audio sans fil réduction de bruit', 'ar' => 'سماعة رأس لاسلكية عازلة للضوضاء'],
                'category' => 'electronique',
                'variants' => [
                    ['ref' => 'v1', 'size' => null, 'color' => 'Noir', 'price_eur' => 49.99],
                ],
            ],
            [
                'ref' => 'mediamarkt-tv-led-43',
                'name' => ['fr' => 'Téléviseur LED 43" Smart TV', 'ar' => 'تلفاز LED ذكي 43 بوصة'],
                'category' => 'electronique',
                'variants' => [
                    ['ref' => 'v1', 'size' => null, 'color' => null, 'price_eur' => 349.99],
                ],
            ],
        ],
    ];

    public function run(): void
    {
        $categoryIds = [];
        foreach (self::CATEGORIES as $slug => $name) {
            $categoryIds[$slug] = Category::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $name]
            )->id;
        }

        foreach (self::PRODUCTS_BY_BOUTIQUE as $boutiqueSlug => $products) {
            $boutique = Boutique::query()->where('slug', $boutiqueSlug)->first();

            if ($boutique === null) {
                // La boutique n'existe pas (DemoBoutiqueSeeder pas encore
                // lancé) : on saute plutôt que d'échouer, ce seeder reste
                // rejouable dans n'importe quel ordre après coup.
                continue;
            }

            foreach ($products as $productData) {
                $minPrice = min(array_column($productData['variants'], 'price_eur'));

                $product = Product::query()->updateOrCreate(
                    ['boutique_id' => $boutique->id, 'external_ref' => $productData['ref']],
                    [
                        'category_id' => $categoryIds[$productData['category']],
                        'name' => $productData['name'],
                        'base_price_eur' => $minPrice,
                        'status' => ProductStatus::ACTIVE,
                        'source_url' => $boutique->base_url,
                        'last_synced_at' => now(),
                    ]
                );

                foreach ($productData['variants'] as $variant) {
                    $product->variants()->updateOrCreate(
                        ['external_variant_ref' => $variant['ref']],
                        [
                            'sku' => Str::upper($productData['ref'].'-'.$variant['ref']),
                            'size' => $variant['size'],
                            'color' => $variant['color'],
                            'price_eur' => $variant['price_eur'],
                            'stock_status' => 'in_stock',
                        ]
                    );
                }
            }
        }
    }
}

<?php

namespace App\DataTransferObjects\Catalog;

/**
 * Représentation normalisée d'un produit tel que récupéré depuis le site
 * d'une boutique, avant traduction et mapping de catégorie. Les champs
 * texte (name/description) sont dans la langue source du site (souvent
 * l'espagnol — 8.10 du cahier des charges).
 */
readonly class RawCatalogProduct
{
    /**
     * @param  array<string>  $images
     * @param  array<RawCatalogVariant>  $variants
     */
    public function __construct(
        public string $externalRef,
        public string $name,
        public ?string $description,
        public array $images,
        public string $sourceCategoryRef,
        public string $sourceUrl,
        public array $variants,
    ) {}
}

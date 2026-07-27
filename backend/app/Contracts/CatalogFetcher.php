<?php

namespace App\Contracts;

use App\DataTransferObjects\Catalog\RawCatalogProduct;
use App\Models\Boutique;

interface CatalogFetcher
{
    /**
     * Récupère l'état courant du catalogue d'une boutique. Doit lever une
     * exception en cas d'échec (réseau, changement de structure du site,
     * etc.) — CatalogSyncService s'occupe alors de conserver le dernier
     * catalogue valide connu (règle 8.2.2) et de journaliser l'erreur.
     *
     * @return iterable<RawCatalogProduct>
     */
    public function fetch(Boutique $boutique): iterable;
}

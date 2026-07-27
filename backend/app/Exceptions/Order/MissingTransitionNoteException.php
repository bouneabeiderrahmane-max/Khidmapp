<?php

namespace App\Exceptions\Order;

use RuntimeException;

/**
 * Motif obligatoire pour un remboursement (8.4.1) ou une annulation
 * au-delà de la fenêtre gratuite (8.4.1 : frais possibles ou retour requis).
 */
class MissingTransitionNoteException extends RuntimeException
{
    public static function forRefund(): self
    {
        return new self('Un motif est obligatoire pour passer une commande au statut "Remboursé".');
    }

    public static function forLateCancellation(): self
    {
        return new self('Un motif est obligatoire pour annuler cette commande au-delà de la fenêtre d\'annulation gratuite.');
    }
}

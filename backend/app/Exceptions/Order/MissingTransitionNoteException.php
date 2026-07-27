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
        return new self(__('khidmapp.transition_note_required_refund'));
    }

    public static function forLateCancellation(): self
    {
        return new self(__('khidmapp.transition_note_required_late_cancellation'));
    }
}

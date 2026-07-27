<?php

namespace App\Services\Catalog;

use App\Contracts\TranslatorGateway;

/**
 * Placeholder : ne traduit rien, recopie le texte source tel quel dans les
 * deux langues. Le cahier des charges (8.2.1, 8.10) demande une traduction
 * automatique FR/AR des titres et descriptions synchronisés, avec
 * correction manuelle possible — mais ne précise aucun service de
 * traduction à utiliser (Google Cloud Translation, DeepL, etc.), ce choix
 * n'a pas été fait dans cette session.
 *
 * Remplacer par une vraie implémentation de TranslatorGateway une fois un
 * service de traduction choisi. En attendant, un administrateur peut
 * toujours corriger manuellement les champs `name`/`description` d'un
 * produit (correction manuelle prévue par le CDC), ce que l'API permet déjà.
 */
class PassthroughTranslator implements TranslatorGateway
{
    /**
     * @return array{fr: string, ar: string}
     */
    public function translate(string $text): array
    {
        return ['fr' => $text, 'ar' => $text];
    }
}

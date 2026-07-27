<?php

namespace App\Contracts;

interface TranslatorGateway
{
    /**
     * Traduit un texte source (souvent l'espagnol) vers le français et
     * l'arabe.
     *
     * @return array{fr: string, ar: string}
     */
    public function translate(string $text): array;
}

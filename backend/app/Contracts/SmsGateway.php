<?php

namespace App\Contracts;

interface SmsGateway
{
    /**
     * Send a text message to the given phone number (E.164-ish format,
     * e.g. +2224XXXXXXX). Implementations are swapped via the binding in
     * AppServiceProvider so a real operator gateway can replace the
     * placeholder without touching any caller.
     */
    public function send(string $phone, string $message): void;
}

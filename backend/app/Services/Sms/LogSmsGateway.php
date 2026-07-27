<?php

namespace App\Services\Sms;

use App\Contracts\SmsGateway;
use Illuminate\Support\Facades\Log;

/**
 * Placeholder gateway: writes the message to the log instead of delivering
 * a real SMS. The cahier des charges does not name an SMS operator/gateway
 * for Mauritania, so this stands in until one is chosen — do not treat this
 * as a production-ready delivery channel.
 */
class LogSmsGateway implements SmsGateway
{
    public function send(string $phone, string $message): void
    {
        Log::info('[SMS placeholder] would send to '.$phone, ['message' => $message]);
    }
}

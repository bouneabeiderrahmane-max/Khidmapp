<?php

namespace App\Services\Auth;

use App\Contracts\SmsGateway;
use App\Models\OtpCode;
use Illuminate\Support\Facades\Hash;

class OtpService
{
    private const CODE_LENGTH = 6;

    private const TTL_MINUTES = 5;

    private const MAX_ATTEMPTS = 5;

    public function __construct(private readonly SmsGateway $sms) {}

    public function generateAndSend(string $phone): void
    {
        $code = (string) random_int(10 ** (self::CODE_LENGTH - 1), (10 ** self::CODE_LENGTH) - 1);

        OtpCode::query()
            ->where('phone', $phone)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        OtpCode::query()->create([
            'phone' => $phone,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
        ]);

        $this->sms->send($phone, __('khidmapp.otp_message', ['code' => $code]));
    }

    /**
     * Verify the code for the given phone. Returns true and consumes the
     * code on success; returns false (and counts the attempt) otherwise.
     */
    public function verify(string $phone, string $code): bool
    {
        $otp = OtpCode::query()
            ->where('phone', $phone)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if (! $otp || $otp->isExpired() || $otp->attempts >= self::MAX_ATTEMPTS) {
            return false;
        }

        if (! $otp->matches($code)) {
            $otp->increment('attempts');

            return false;
        }

        $otp->update(['consumed_at' => now()]);

        return true;
    }
}

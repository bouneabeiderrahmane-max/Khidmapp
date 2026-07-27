<?php

namespace App\Exceptions\Complaint;

use RuntimeException;

class InvalidComplaintTransitionException extends RuntimeException
{
    public static function from(string $fromStatus, string $toStatus): self
    {
        return new self(__('khidmapp.invalid_complaint_transition', ['from' => $fromStatus, 'to' => $toStatus]));
    }
}

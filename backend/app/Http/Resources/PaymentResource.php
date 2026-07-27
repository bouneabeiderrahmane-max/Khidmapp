<?php

namespace App\Http\Resources;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Payment */
class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'method' => $this->method,
            'method_label' => __('khidmapp.payment_method.'.$this->method),
            'status' => $this->status,
            'status_label' => __('khidmapp.payment_status.'.$this->status),
            'amount_mru' => $this->amount_mru,
            'external_reference' => $this->external_reference,
            'rejection_reason' => $this->rejection_reason,
            'info_requested_note' => $this->info_requested_note,
            'proof_download_url' => $this->proof_file_path !== null ? route('admin.payments.proof', $this->id) : null,
            'initiated_at' => $this->initiated_at,
            'confirmed_at' => $this->confirmed_at,
        ];
    }
}

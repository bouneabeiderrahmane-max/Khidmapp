<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Support\OrderStatus;
use App\Support\PaymentMethod;
use App\Support\PaymentStatus;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $serviceClient;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');

        $this->admin = User::factory()->create();
        $this->admin->assignRole(Roles::ADMINISTRATEUR);

        $this->serviceClient = User::factory()->create();
        $this->serviceClient->assignRole(Roles::SERVICE_CLIENT);

        $this->client = User::factory()->create();
        $this->client->assignRole(Roles::CLIENT);
    }

    private function pendingManualPayment(): Payment
    {
        $order = Order::query()->create([
            'user_id' => $this->client->id, 'status' => OrderStatus::AWAITING_PAYMENT, 'payment_method' => PaymentMethod::MANUAL,
            'subtotal_eur' => 10, 'exchange_rate_snapshot' => 10, 'subtotal_mru' => 100,
            'delivery_fee_snapshot_mru' => 200, 'total_mru' => 300,
        ]);

        $path = UploadedFile::fake()->image('proof.jpg')->store('payment-proofs');

        return Payment::query()->create([
            'order_id' => $order->id, 'submitted_by' => $this->client->id, 'method' => PaymentMethod::MANUAL,
            'status' => PaymentStatus::PENDING, 'amount_mru' => 300, 'proof_file_path' => $path, 'initiated_at' => now(),
        ]);
    }

    public function test_a_client_is_forbidden_from_every_admin_payment_endpoint(): void
    {
        $payment = $this->pendingManualPayment();

        $this->actingAs($this->client, 'api')->getJson('/api/v1/admin/payments/manual')->assertForbidden();
        $this->actingAs($this->client, 'api')->postJson("/api/v1/admin/payments/{$payment->id}/validate")->assertForbidden();
    }

    public function test_service_client_can_list_pending_manual_payments(): void
    {
        $this->pendingManualPayment();

        $this->actingAs($this->serviceClient, 'api')
            ->getJson('/api/v1/admin/payments/manual?status=pending')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_validating_a_proof_transitions_the_order_and_records_the_agent(): void
    {
        $payment = $this->pendingManualPayment();

        $this->actingAs($this->serviceClient, 'api')
            ->postJson("/api/v1/admin/payments/{$payment->id}/validate")
            ->assertOk()
            ->assertJsonPath('data.status', 'validated');

        $this->assertSame(OrderStatus::PAYMENT_VALIDATED, $payment->order->fresh()->status);
        $this->assertSame($this->serviceClient->id, $payment->fresh()->reviewed_by);
    }

    public function test_rejecting_a_proof_requires_a_reason(): void
    {
        $payment = $this->pendingManualPayment();

        $this->actingAs($this->serviceClient, 'api')
            ->postJson("/api/v1/admin/payments/{$payment->id}/reject")
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);

        $this->actingAs($this->serviceClient, 'api')
            ->postJson("/api/v1/admin/payments/{$payment->id}/reject", ['reason' => 'Preuve illisible.'])
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected');

        $this->assertSame(OrderStatus::AWAITING_PAYMENT, $payment->order->fresh()->status);
    }

    public function test_requesting_more_info_requires_a_note(): void
    {
        $payment = $this->pendingManualPayment();

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/admin/payments/{$payment->id}/request-info")
            ->assertStatus(422)
            ->assertJsonValidationErrors(['note']);

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/admin/payments/{$payment->id}/request-info", ['note' => 'Montant illisible, merci de renvoyer.'])
            ->assertOk()
            ->assertJsonPath('data.status', 'info_requested');
    }

    public function test_downloading_the_proof_file_streams_it(): void
    {
        $payment = $this->pendingManualPayment();

        $this->actingAs($this->admin, 'api')
            ->get("/api/v1/admin/payments/{$payment->id}/proof")
            ->assertOk();
    }

    public function test_a_proof_already_reviewed_cannot_be_validated_again(): void
    {
        $payment = $this->pendingManualPayment();
        $this->actingAs($this->admin, 'api')->postJson("/api/v1/admin/payments/{$payment->id}/reject", ['reason' => 'Illisible.']);

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/admin/payments/{$payment->id}/validate")
            ->assertStatus(422);
    }
}

<?php

namespace Tests\Unit\Services;

use App\Exceptions\Complaint\InvalidComplaintTransitionException;
use App\Models\Order;
use App\Models\User;
use App\Services\Complaint\ComplaintService;
use App\Support\ComplaintCategory;
use App\Support\ComplaintStatus;
use App\Support\OrderActorType;
use App\Support\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplaintServiceTest extends TestCase
{
    use RefreshDatabase;

    private ComplaintService $service;

    private User $client;

    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ComplaintService::class);
        $this->client = User::factory()->create();
        $this->agent = User::factory()->create();
    }

    private function makeOrder(): Order
    {
        return Order::query()->create([
            'user_id' => $this->client->id, 'status' => OrderStatus::DELIVERED, 'payment_method' => 'manual',
            'subtotal_eur' => 10, 'exchange_rate_snapshot' => 10, 'subtotal_mru' => 100,
            'delivery_fee_snapshot_mru' => 200, 'total_mru' => 300,
        ]);
    }

    public function test_opening_a_complaint_creates_the_first_client_message(): void
    {
        $order = $this->makeOrder();

        $complaint = $this->service->open($this->client, $order, ComplaintCategory::PRODUIT_NON_CONFORME, 'Article endommagé.');

        $this->assertSame(ComplaintStatus::OUVERTE, $complaint->status);
        $this->assertCount(1, $complaint->messages);
        $this->assertSame(OrderActorType::CLIENT, $complaint->messages->first()->sender_type);
    }

    public function test_opening_a_complaint_automatically_creates_a_system_message_without_a_sender(): void
    {
        $order = $this->makeOrder();

        $complaint = $this->service->openAutomatically($order, ComplaintCategory::PRODUIT_NON_CONFORME, 'Anomalie détectée automatiquement.');

        $this->assertSame(ComplaintStatus::OUVERTE, $complaint->status);
        $this->assertSame($order->user_id, $complaint->user_id);
        $this->assertCount(1, $complaint->messages);
        $this->assertSame(OrderActorType::SYSTEM, $complaint->messages->first()->sender_type);
        $this->assertNull($complaint->messages->first()->sender_id);
    }

    public function test_an_agent_reply_on_an_open_complaint_takes_it_in_charge(): void
    {
        $order = $this->makeOrder();
        $complaint = $this->service->open($this->client, $order, ComplaintCategory::RETARD, 'Colis en retard.');

        $this->service->addMessage($complaint, $this->agent, OrderActorType::SERVICE_CLIENT, 'Nous regardons cela.');

        $this->assertSame(ComplaintStatus::EN_COURS, $complaint->fresh()->status);
    }

    public function test_a_client_reply_while_awaiting_client_returns_the_complaint_to_in_progress(): void
    {
        $order = $this->makeOrder();
        $complaint = $this->service->open($this->client, $order, ComplaintCategory::AUTRE, 'Question.');
        $this->service->updateStatus($complaint, ComplaintStatus::EN_COURS);
        $this->service->updateStatus($complaint, ComplaintStatus::EN_ATTENTE_CLIENT);

        $this->service->addMessage($complaint, $this->client, OrderActorType::CLIENT, 'Voici la précision demandée.');

        $this->assertSame(ComplaintStatus::EN_COURS, $complaint->fresh()->status);
    }

    public function test_a_client_reply_on_an_open_complaint_does_not_change_its_status(): void
    {
        $order = $this->makeOrder();
        $complaint = $this->service->open($this->client, $order, ComplaintCategory::AUTRE, 'Question.');

        $this->service->addMessage($complaint, $this->client, OrderActorType::CLIENT, 'Un détail de plus.');

        $this->assertSame(ComplaintStatus::OUVERTE, $complaint->fresh()->status);
    }

    public function test_updating_to_a_disallowed_status_is_rejected(): void
    {
        $order = $this->makeOrder();
        $complaint = $this->service->open($this->client, $order, ComplaintCategory::AUTRE, 'Question.');

        $this->expectException(InvalidComplaintTransitionException::class);
        $this->service->updateStatus($complaint, ComplaintStatus::RESOLUE);
    }

    public function test_a_closed_complaint_cannot_transition_further(): void
    {
        $order = $this->makeOrder();
        $complaint = $this->service->open($this->client, $order, ComplaintCategory::AUTRE, 'Question.');
        $this->service->updateStatus($complaint, ComplaintStatus::CLOTUREE);

        $this->expectException(InvalidComplaintTransitionException::class);
        $this->service->updateStatus($complaint, ComplaintStatus::EN_COURS);
    }
}

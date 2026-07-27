<?php

namespace Tests\Feature\Catalog;

use App\Jobs\SyncBoutiqueCatalog;
use App\Models\Boutique;
use App\Models\SyncLog;
use App\Models\User;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BoutiqueSyncTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole(Roles::ADMINISTRATEUR);
    }

    public function test_triggering_a_sync_dispatches_the_job(): void
    {
        Queue::fake();
        $boutique = Boutique::factory()->create();

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/admin/boutiques/{$boutique->id}/sync")
            ->assertStatus(202);

        Queue::assertPushed(SyncBoutiqueCatalog::class, fn ($job) => $job->boutiqueId === $boutique->id);
    }

    public function test_a_client_cannot_trigger_a_sync(): void
    {
        $client = User::factory()->create();
        $client->assignRole(Roles::CLIENT);
        $boutique = Boutique::factory()->create();

        $this->actingAs($client, 'api')
            ->postJson("/api/v1/admin/boutiques/{$boutique->id}/sync")
            ->assertStatus(403);
    }

    public function test_the_sync_log_journal_is_paginated_and_ordered_by_most_recent(): void
    {
        $boutique = Boutique::factory()->create();
        SyncLog::query()->create(['boutique_id' => $boutique->id, 'status' => 'success', 'started_at' => now()->subDay()]);
        SyncLog::query()->create(['boutique_id' => $boutique->id, 'status' => 'failed', 'started_at' => now()]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson("/api/v1/admin/boutiques/{$boutique->id}/sync-logs");

        $response->assertOk();
        $this->assertSame('failed', $response->json('data.0.status'));
    }
}

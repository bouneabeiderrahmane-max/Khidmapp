<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\AuditAction;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(Roles::ADMINISTRATEUR);

        $this->client = User::factory()->create();
        $this->client->assignRole(Roles::CLIENT);
    }

    public function test_blocking_a_user_actually_persists_and_is_reflected_immediately(): void
    {
        $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/admin/users/{$this->client->id}/block", ['reason' => 'Abus signalé.'])
            ->assertOk()
            ->assertJsonPath('data.is_blocked', true)
            ->assertJsonPath('data.blocked_reason', 'Abus signalé.');

        $this->assertTrue($this->client->fresh()->isBlocked());
        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::USER_BLOCKED,
            'subject_id' => $this->client->id,
        ]);
    }

    public function test_a_blocked_user_cannot_use_an_already_issued_token(): void
    {
        $this->client->update(['blocked_at' => now(), 'blocked_reason' => 'Test']);

        $this->actingAs($this->client, 'api')
            ->getJson('/api/v1/me')
            ->assertStatus(403);
    }

    public function test_unblocking_a_user_clears_the_block(): void
    {
        $this->client->update(['blocked_at' => now(), 'blocked_reason' => 'Test']);

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/admin/users/{$this->client->id}/unblock")
            ->assertOk()
            ->assertJsonPath('data.is_blocked', false);

        $this->assertFalse($this->client->fresh()->isBlocked());
        $this->assertDatabaseHas('audit_logs', ['action' => AuditAction::USER_UNBLOCKED]);
    }

    public function test_an_administrateur_can_create_an_internal_account(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/users', [
                'name' => 'Nouvel agent',
                'email' => 'agent@khidmapp.test',
                'password' => 'password123',
                'role' => Roles::SERVICE_CLIENT,
            ]);

        $response->assertCreated()->assertJsonPath('data.roles', [Roles::SERVICE_CLIENT]);
        $this->assertDatabaseHas('audit_logs', ['action' => AuditAction::USER_CREATED]);
    }

    public function test_roles_can_be_replaced_for_an_internal_account(): void
    {
        $agent = User::factory()->create();
        $agent->assignRole(Roles::SERVICE_CLIENT);

        $this->actingAs($this->admin, 'api')
            ->putJson("/api/v1/admin/users/{$agent->id}/roles", ['roles' => [Roles::ADMINISTRATEUR]])
            ->assertOk()
            ->assertJsonPath('data.roles', [Roles::ADMINISTRATEUR]);

        $log = AuditLog::query()->where('action', AuditAction::USER_ROLE_ASSIGNED)->firstOrFail();
        $this->assertSame([Roles::SERVICE_CLIENT], $log->changes['from']);
        $this->assertSame([Roles::ADMINISTRATEUR], $log->changes['to']);
    }

    public function test_an_internal_role_cannot_be_assigned_to_a_client(): void
    {
        $this->actingAs($this->admin, 'api')
            ->putJson("/api/v1/admin/users/{$this->client->id}/roles", ['roles' => [Roles::ADMINISTRATEUR]])
            ->assertStatus(422);
    }

    public function test_a_service_client_cannot_manage_users(): void
    {
        $agent = User::factory()->create();
        $agent->assignRole(Roles::SERVICE_CLIENT);

        $this->actingAs($agent, 'api')
            ->getJson('/api/v1/admin/users')
            ->assertStatus(403);
    }
}

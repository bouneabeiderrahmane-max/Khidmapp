<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditAction;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogControllerTest extends TestCase
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

    public function test_an_administrateur_can_list_audit_logs(): void
    {
        app(AuditLogger::class)->log($this->admin, AuditAction::MARGIN_RULE_CREATED, null, ['percent' => 25]);
        app(AuditLogger::class)->log($this->admin, AuditAction::EXCHANGE_RATE_CREATED, null, ['rate' => 47.5]);

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/audit-logs')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_audit_logs_can_be_filtered_by_action(): void
    {
        app(AuditLogger::class)->log($this->admin, AuditAction::MARGIN_RULE_CREATED, null, ['percent' => 25]);
        app(AuditLogger::class)->log($this->admin, AuditAction::EXCHANGE_RATE_CREATED, null, ['rate' => 47.5]);

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/audit-logs?action='.AuditAction::MARGIN_RULE_CREATED)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.action', AuditAction::MARGIN_RULE_CREATED);
    }

    public function test_a_service_client_cannot_view_audit_logs(): void
    {
        $agent = User::factory()->create();
        $agent->assignRole(Roles::SERVICE_CLIENT);

        $this->actingAs($agent, 'api')
            ->getJson('/api/v1/admin/audit-logs')
            ->assertStatus(403);
    }
}

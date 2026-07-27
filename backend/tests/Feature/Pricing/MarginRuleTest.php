<?php

namespace Tests\Feature\Pricing;

use App\Models\Boutique;
use App\Models\User;
use App\Support\AuditAction;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarginRuleTest extends TestCase
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

    public function test_it_creates_a_global_margin_rule_without_a_scope_id(): void
    {
        $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/margin-rules', ['scope_type' => 'global', 'percent' => 25])
            ->assertCreated()
            ->assertJsonPath('data.scope_id', null);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::MARGIN_RULE_CREATED,
        ]);
    }

    public function test_a_boutique_scope_requires_a_valid_scope_id(): void
    {
        $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/margin-rules', ['scope_type' => 'boutique', 'scope_id' => 999999, 'percent' => 25])
            ->assertStatus(422);
    }

    public function test_it_creates_a_boutique_scoped_margin_rule(): void
    {
        $boutique = Boutique::factory()->create();

        $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/margin-rules', [
                'scope_type' => 'boutique',
                'scope_id' => $boutique->id,
                'percent' => 30,
            ])
            ->assertCreated()
            ->assertJsonPath('data.scope_id', $boutique->id);
    }

    public function test_a_client_cannot_manage_margin_rules(): void
    {
        $client = User::factory()->create();
        $client->assignRole(Roles::CLIENT);

        $this->actingAs($client, 'api')
            ->postJson('/api/v1/admin/margin-rules', ['scope_type' => 'global', 'percent' => 25])
            ->assertStatus(403);
    }
}

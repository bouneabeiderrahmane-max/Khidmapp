<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFaqControllerTest extends TestCase
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

    public function test_a_client_is_forbidden(): void
    {
        $client = User::factory()->create();
        $client->assignRole(Roles::CLIENT);

        $this->actingAs($client, 'api')->postJson('/api/v1/admin/faqs', [])->assertForbidden();
    }

    public function test_service_client_is_forbidden(): void
    {
        $agent = User::factory()->create();
        $agent->assignRole(Roles::SERVICE_CLIENT);

        $this->actingAs($agent, 'api')->postJson('/api/v1/admin/faqs', [])->assertForbidden();
    }

    public function test_an_administrateur_can_create_update_and_delete_a_faq(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/faqs', [
                'question' => ['fr' => 'Comment suivre ma commande ?', 'ar' => 'كيف أتتبع طلبي؟'],
                'answer' => ['fr' => 'Depuis la page commande.', 'ar' => 'من صفحة الطلب.'],
                'category' => 'commandes',
            ])
            ->assertCreated();

        $faqId = $response->json('data.id');

        $this->actingAs($this->admin, 'api')
            ->putJson("/api/v1/admin/faqs/{$faqId}", ['position' => 5])
            ->assertOk()
            ->assertJsonPath('data.position', 5);

        $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/v1/admin/faqs/{$faqId}")
            ->assertOk();

        $this->assertDatabaseMissing('faqs', ['id' => $faqId]);
    }

    public function test_creating_a_faq_requires_both_languages(): void
    {
        $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/faqs', ['question' => ['fr' => 'Question ?'], 'answer' => ['fr' => 'Réponse.']])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['question.ar', 'answer.ar']);
    }
}

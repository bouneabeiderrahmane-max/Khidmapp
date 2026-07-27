<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_their_profile(): void
    {
        $user = User::factory()->create(['name' => 'Aissata']);

        $this->actingAs($user, 'api')
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.name', 'Aissata');
    }

    public function test_user_can_update_name_and_locale(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'api')
            ->putJson('/api/v1/me', ['name' => 'Nouveau nom', 'locale' => 'ar'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Nouveau nom')
            ->assertJsonPath('data.locale', 'ar');
    }

    public function test_user_cannot_update_email_to_one_already_taken(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create();

        $this->actingAs($user, 'api')
            ->putJson('/api/v1/me', ['email' => 'taken@example.com'])
            ->assertStatus(422);
    }

    public function test_user_can_deactivate_their_account_and_is_signed_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'api')
            ->deleteJson('/api/v1/me')
            ->assertOk();

        $this->assertSoftDeleted($user);
        $this->assertNull(User::find($user->id));
    }
}

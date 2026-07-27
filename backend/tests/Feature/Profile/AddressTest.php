<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddressTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_first_address_created_is_automatically_the_default(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'api')
            ->postJson('/api/v1/addresses', ['label' => 'Domicile', 'city' => 'Nouakchott'])
            ->assertCreated()
            ->assertJsonPath('data.is_default', true);
    }

    public function test_marking_a_new_address_as_default_unsets_the_previous_one(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');

        $this->postJson('/api/v1/addresses', ['label' => 'Domicile', 'city' => 'Nouakchott']);
        $this->postJson('/api/v1/addresses', ['label' => 'Bureau', 'city' => 'Nouakchott', 'is_default' => true]);

        $addresses = $this->getJson('/api/v1/addresses')->json('data');
        $defaults = array_filter($addresses, fn ($a) => $a['is_default']);

        $this->assertCount(1, $defaults);
        $this->assertSame('Bureau', array_values($defaults)[0]['label']);
    }

    public function test_a_user_cannot_modify_another_users_address(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $address = $this->actingAs($owner, 'api')
            ->postJson('/api/v1/addresses', ['label' => 'Domicile', 'city' => 'Nouakchott'])
            ->json('data');

        $this->actingAs($intruder, 'api')
            ->putJson("/api/v1/addresses/{$address['id']}", ['label' => 'Piratée', 'city' => 'Nouakchott'])
            ->assertStatus(403);

        $this->actingAs($intruder, 'api')
            ->deleteJson("/api/v1/addresses/{$address['id']}")
            ->assertStatus(403);
    }

    public function test_a_user_can_update_and_delete_their_address(): void
    {
        $user = User::factory()->create();
        $address = $this->actingAs($user, 'api')
            ->postJson('/api/v1/addresses', ['label' => 'Domicile', 'city' => 'Nouakchott'])
            ->json('data');

        $this->actingAs($user, 'api')
            ->putJson("/api/v1/addresses/{$address['id']}", ['label' => 'Maison', 'city' => 'Nouakchott'])
            ->assertOk()
            ->assertJsonPath('data.label', 'Maison');

        $this->actingAs($user, 'api')
            ->deleteJson("/api/v1/addresses/{$address['id']}")
            ->assertOk();

        $this->assertDatabaseMissing('addresses', ['id' => $address['id']]);
    }
}

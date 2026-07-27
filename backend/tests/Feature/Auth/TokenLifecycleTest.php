<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TokenLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_a_protected_route_rejects_a_request_without_a_token(): void
    {
        $this->getJson('/api/v1/me')->assertStatus(401);
    }

    public function test_refresh_returns_a_new_valid_token(): void
    {
        $user = User::factory()->create(['password' => 'password123']);
        $token = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->json('access_token');

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v1/auth/refresh');

        $response->assertOk()->assertJsonStructure(['access_token']);
        $newToken = $response->json('access_token');
        $this->assertNotSame($token, $newToken);

        $this->withHeader('Authorization', "Bearer $newToken")
            ->getJson('/api/v1/me')
            ->assertOk();
    }

    public function test_logout_invalidates_the_token(): void
    {
        $user = User::factory()->create(['password' => 'password123']);
        $token = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->json('access_token');

        $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/v1/me')
            ->assertStatus(401);
    }
}

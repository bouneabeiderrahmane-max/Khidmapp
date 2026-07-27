<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_health_endpoint_is_reachable(): void
    {
        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertJson(['status' => 'ok']);
    }

    public function test_health_endpoint_honours_accept_language(): void
    {
        $this->getJson('/api/v1/health', ['Accept-Language' => 'ar'])
            ->assertOk()
            ->assertJson(['locale' => 'ar']);

        $this->getJson('/api/v1/health', ['Accept-Language' => 'fr'])
            ->assertOk()
            ->assertJson(['locale' => 'fr']);
    }
}

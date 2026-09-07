<?php

namespace Tests\Feature;

use Tests\TestCase;

class StatusApiTest extends TestCase
{
    public function test_status_endpoint_returns_a_successful_response(): void
    {
        $response = $this->getJson('/api/status');

        $response->assertOk()->assertJson([
            'data' => [
                'application' => 'SindicoPro',
                'status' => 'ok',
            ],
        ]);
    }
}

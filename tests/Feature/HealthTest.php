<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_health_endpoint_reports_core_services(): void
    {
        config([
            'ai.driver' => 'mock',
            'queue.default' => 'sync',
        ]);

        $this->get('/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('checks.database', true)
            ->assertJsonPath('checks.cache', true)
            ->assertJsonPath('checks.queue', true)
            ->assertJsonPath('checks.ai', true);
    }
}

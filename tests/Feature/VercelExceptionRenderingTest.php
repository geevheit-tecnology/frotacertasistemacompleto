<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VercelExceptionRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_vercel_json_handler_preserves_http_status_codes(): void
    {
        config(['app.env' => 'production']);
        putenv('VERCEL=1');
        $_ENV['VERCEL'] = '1';

        try {
            $this->getJson('/api/route-that-does-not-exist')
                ->assertStatus(404)
                ->assertJsonPath('status', 404);
        } finally {
            putenv('VERCEL');
            unset($_ENV['VERCEL']);
        }
    }
}

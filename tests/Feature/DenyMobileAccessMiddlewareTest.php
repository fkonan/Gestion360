<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DenyMobileAccessMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('deny.mobile')->get('/__test/deny-mobile', fn () => 'ok');
    }

    public function test_allows_desktop_user_agent(): void
    {
        $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124.0 Safari/537.36',
        ])->get('/__test/deny-mobile')
            ->assertOk()
            ->assertSee('ok');
    }

    public function test_blocks_mobile_user_agent_with_403(): void
    {
        $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Linux; Android 14; Pixel 7) AppleWebKit/537.36 Chrome/124.0 Mobile Safari/537.36',
        ])->get('/__test/deny-mobile')
            ->assertStatus(302)
            ->assertSessionHas('alert');
    }

    public function test_blocks_when_desktop_browser_emulates_mobile_user_agent(): void
    {
        $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) AppleWebKit/605.1.15 Version/17.4 Mobile/15E148 Safari/604.1',
        ])->get('/__test/deny-mobile')
            ->assertStatus(302);
    }

    public function test_returns_json_for_ajax_or_json_requests(): void
    {
        $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Linux; Android 13; SM-A546B) AppleWebKit/537.36 Chrome/123.0 Mobile Safari/537.36',
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ])->getJson('/__test/deny-mobile')
            ->assertForbidden()
            ->assertJson([
                'ok' => false,
                'message' => 'Este módulo no está disponible en móviles.',
            ]);
    }
}

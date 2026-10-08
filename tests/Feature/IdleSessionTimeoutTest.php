<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\IdleSessionTimeout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class IdleSessionTimeoutTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role'                    => 'CFO',
            'two_factor_confirmed_at' => now(),
        ]);
    }

    public function test_active_user_within_3_minute_window_is_not_logged_out(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession([
                'auth.2fa_passed'       => true,
                'auth.last_activity_at' => now()->subSeconds(60)->toIso8601String(),
            ])
            ->get('/accounting/dashboard');

        $response->assertOk();
        $this->assertAuthenticatedAs($this->user);
    }

    public function test_user_idle_for_more_than_180_seconds_is_redirected_to_login_with_flash_message(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession([
                'auth.2fa_passed'       => true,
                'auth.last_activity_at' => now()->subSeconds(181)->toIso8601String(),
            ])
            ->get('/accounting/dashboard');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('session_expired');
        $this->assertGuest();
    }

    public function test_idle_json_request_after_180_seconds_returns_401_response(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession([
                'auth.2fa_passed'       => true,
                'auth.last_activity_at' => now()->subSeconds(181)->toIso8601String(),
            ])
            ->getJson('/accounting/dashboard');

        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Session expired due to inactivity.',
        ]);
        $this->assertGuest();
    }

    public function test_heartbeat_with_touch_refreshes_last_activity_timestamp(): void
    {
        $pastActivity = now()->subSeconds(120)->toIso8601String();

        $response = $this->actingAs($this->user)
            ->withSession([
                'auth.2fa_passed'       => true,
                'auth.last_activity_at' => $pastActivity,
            ])
            ->postJson('/session/heartbeat', ['touch' => true]);

        $response->assertOk();
        $response->assertJson([
            'status'       => 'ok',
            'remaining'    => 180,
            'idle_seconds' => 0,
        ]);

        $updatedActivity = session('auth.last_activity_at');
        $this->assertNotSame($pastActivity, $updatedActivity);
        $this->assertAuthenticatedAs($this->user);
    }

    public function test_heartbeat_without_touch_does_not_refresh_timestamp_and_returns_remaining_seconds(): void
    {
        $pastTime = now()->subSeconds(50);
        $pastActivity = $pastTime->toIso8601String();

        $response = $this->actingAs($this->user)
            ->withSession([
                'auth.2fa_passed'       => true,
                'auth.last_activity_at' => $pastActivity,
            ])
            ->postJson('/session/heartbeat', ['touch' => false]);

        $response->assertOk();
        $data = $response->json();

        $this->assertSame('ok', $data['status']);
        $this->assertGreaterThanOrEqual(125, $data['remaining']);
        $this->assertLessThanOrEqual(135, $data['remaining']);

        // The session's last_activity_at should remain unchanged
        $this->assertSame($pastActivity, session('auth.last_activity_at'));
    }

    public function test_heartbeat_without_touch_after_180_seconds_terminates_session(): void
    {
        $pastActivity = now()->subSeconds(181)->toIso8601String();

        $response = $this->actingAs($this->user)
            ->withSession([
                'auth.2fa_passed'       => true,
                'auth.last_activity_at' => $pastActivity,
            ])
            ->postJson('/session/heartbeat', ['touch' => false]);

        $response->assertStatus(401);
        $response->assertJson([
            'status'  => 'expired',
            'message' => 'Session expired due to inactivity.',
        ]);
        $this->assertGuest();
    }

    public function test_logout_with_idle_reason_flashes_session_expired(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession([
                'auth.2fa_passed' => true,
            ])
            ->post('/logout', ['reason' => 'idle']);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('session_expired');
        $this->assertGuest();
    }
}

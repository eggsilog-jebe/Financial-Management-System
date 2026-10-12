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

        $response->assertRedirect(route('login', ['reason' => 'idle', 'expired' => 1]));
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
            'message' => 'Session expired due to inactivity (3 minutes).',
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
            'message' => 'Session expired due to inactivity (3 minutes).',
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

        $response->assertRedirect(route('login', ['reason' => 'idle', 'expired' => 1]));
        $response->assertSessionHas('session_expired');
        $this->assertGuest();
    }

    public function test_login_page_renders_idle_modal_when_redirected_with_idle_timeout(): void
    {
        $response = $this->withSession([
            'session_expired' => 'Your session was automatically terminated because you were idling for 3 minutes. Please sign in again.',
        ])->get(route('login', ['reason' => 'idle', 'expired' => 1]));

        $response->assertOk();
        // Alpine data initializes idleTimeoutModalOpen to true
        $response->assertSee('idleTimeoutModalOpen: true', false);

        // Modal content indicating 3 minutes idle
        $response->assertSee('Session Forcefully Logged Out');
        $response->assertSee('IDLING FOR 3 MINUTES');
        $response->assertSee('3 MINS ELAPSED');
        $response->assertSee('Return to Sign In');
    }

    public function test_authenticated_layout_does_not_render_warning_modal(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession([
                'auth.2fa_passed'       => true,
                'auth.last_activity_at' => now()->toIso8601String(),
            ])
            ->get('/accounting/dashboard');

        $response->assertOk();
        $response->assertDontSee('id="idleTimeoutModal"', false);
        $response->assertDontSee('Session Expiring Soon');
        $response->assertDontSee('SECONDS REMAINING');
        $response->assertDontSee('id="idle-logout-form"', false);
    }

    public function test_login_page_renders_idle_modal_when_accessed_with_query_param_only(): void
    {
        $response = $this->get(route('login', ['reason' => 'idle']));

        $response->assertOk();
        $response->assertSee('idleTimeoutModalOpen: true', false);
        $response->assertSee('IDLING FOR 3 MINUTES');
    }

    public function test_idle_timeout_modal_does_not_open_again_when_submitting_invalid_credentials(): void
    {
        // 1. Initial idle timeout landing
        $response = $this->withSession([
            'session_expired' => 'Your session was automatically terminated because you were idling for 3 minutes. Please sign in again.',
        ])->get(route('login', ['reason' => 'idle', 'expired' => 1]));

        $response->assertOk();
        $response->assertSee('idleTimeoutModalOpen: true', false);

        // 2. User submits invalid credentials from the idle login URL
        $loginPostResponse = $this->from(route('login', ['reason' => 'idle', 'expired' => 1]))
            ->post('/login', [
                'email'    => 'unknown@hospital.gov.ph',
                'password' => 'WrongPassword123!',
            ]);

        // Must redirect to clean /login, NOT back to the query param idle URL
        $loginPostResponse->assertRedirect(route('login'));
        $loginPostResponse->assertSessionHasErrors('email');
        $loginPostResponse->assertSessionMissing('session_expired');

        // 3. When viewing login page after failed attempt, modal must NOT open
        $loginPageWithErrors = $this->withSession([
            'errors' => session('errors'),
        ])->get(route('login'));

        $loginPageWithErrors->assertOk();
        $loginPageWithErrors->assertSee('idleTimeoutModalOpen: false', false);
        $loginPageWithErrors->assertDontSee('idleTimeoutModalOpen: true', false);
    }
}

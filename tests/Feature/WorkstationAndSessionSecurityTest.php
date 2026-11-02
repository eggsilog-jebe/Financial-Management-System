<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserActiveSession;
use App\Models\UserWorkstation;
use App\Services\Security\WorkstationBindingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

final class WorkstationAndSessionSecurityTest extends TestCase
{
    use RefreshDatabase;

    private Google2FA $google2fa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->google2fa = new Google2FA();
    }

    public function test_unrecognized_workstation_creates_pending_request_and_redirects_to_holding_page(): void
    {
        $user = User::factory()->create([
            'email'    => 'billing@hospital.gov.ph',
            'role'     => 'BillingClerk',
            'password' => Hash::make('HospitalSecure123!'),
        ]);

        $deviceUuid = 'ws-test-unrecognized-uuid-12345';

        $response = $this->withCookie(WorkstationBindingService::COOKIE_NAME, $deviceUuid)
            ->post('/login', [
                'email'       => 'billing@hospital.gov.ph',
                'password'    => 'HospitalSecure123!',
                'device_uuid' => $deviceUuid,
            ]);

        $response->assertRedirect(route('workstation.pending'));

        $this->assertDatabaseHas('user_workstations', [
            'user_id'     => $user->id,
            'device_uuid' => $deviceUuid,
            'status'      => UserWorkstation::STATUS_PENDING,
        ]);
    }

    public function test_approved_workstation_proceeds_to_two_factor_challenge(): void
    {
        $secret = $this->google2fa->generateSecretKey(32);

        $user = User::factory()->create([
            'email'                   => 'cfo@hospital.gov.ph',
            'role'                    => 'CFO',
            'password'                => Hash::make('HospitalSecure123!'),
            'two_factor_confirmed_at' => now(),
            'two_factor_secret'       => Crypt::encryptString($secret),
        ]);

        $deviceUuid = 'ws-test-approved-uuid-12345';

        UserWorkstation::create([
            'user_id'          => $user->id,
            'device_uuid'      => $deviceUuid,
            'workstation_name' => 'CFO Office Terminal',
            'platform'         => 'Windows 11',
            'browser'          => 'Chrome',
            'ip_address'       => '127.0.0.1',
            'status'           => UserWorkstation::STATUS_APPROVED,
            'approved_at'      => now(),
        ]);

        $response = $this->withCookie(WorkstationBindingService::COOKIE_NAME, $deviceUuid)
            ->post('/login', [
                'email'       => 'cfo@hospital.gov.ph',
                'password'    => 'HospitalSecure123!',
                'device_uuid' => $deviceUuid,
            ]);

        $response->assertRedirect(route('two-factor.challenge'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_rejected_workstation_is_blocked_from_logging_in(): void
    {
        $user = User::factory()->create([
            'email'    => 'staff@hospital.gov.ph',
            'role'     => 'StaffAccountant',
            'password' => Hash::make('HospitalSecure123!'),
        ]);

        $deviceUuid = 'ws-test-rejected-uuid';

        UserWorkstation::create([
            'user_id'          => $user->id,
            'device_uuid'      => $deviceUuid,
            'workstation_name' => 'Unauthorized Home Laptop',
            'status'           => UserWorkstation::STATUS_REJECTED,
            'rejected_at'      => now(),
            'rejection_reason' => 'Off-site devices not permitted.',
        ]);

        $response = $this->from('/login')
            ->withCookie(WorkstationBindingService::COOKIE_NAME, $deviceUuid)
            ->post('/login', [
                'email'       => 'staff@hospital.gov.ph',
                'password'    => 'HospitalSecure123!',
                'device_uuid' => $deviceUuid,
            ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_super_admin_can_approve_pending_workstation_and_polling_detects_it(): void
    {
        $admin = User::factory()->create(['role' => 'CFO']);
        $user  = User::factory()->create([
            'role'                    => 'Cashier',
            'two_factor_confirmed_at' => now(),
        ]);

        $deviceUuid = 'ws-test-poll-uuid';

        $workstation = UserWorkstation::create([
            'user_id'          => $user->id,
            'device_uuid'      => $deviceUuid,
            'workstation_name' => 'Cashier POS Terminal 3',
            'status'           => UserWorkstation::STATUS_PENDING,
        ]);

        // User polls status before approval -> pending
        $pendingPoll = $this->actingAs($user)
            ->withSession(['auth.pending_device_uuid' => $deviceUuid])
            ->withHeaders(['X-Workstation-UUID' => $deviceUuid])
            ->getJson(route('workstation.status'));

        $pendingPoll->assertOk();
        $pendingPoll->assertJson(['status' => 'pending']);

        // Admin approves the workstation
        $approveResponse = $this->actingAs($admin)
            ->post(route('user-security.workstations.approve', $workstation), [
                'workstation_name' => 'Cashier Desk Station #3',
            ]);

        $approveResponse->assertSessionHas('success');
        $this->assertTrue($workstation->fresh()->isApproved());

        // User polls status after approval -> approved with redirect URL
        $approvedPoll = $this->actingAs($user)
            ->withSession(['auth.pending_device_uuid' => $deviceUuid])
            ->withHeaders(['X-Workstation-UUID' => $deviceUuid])
            ->getJson(route('workstation.status'));

        $approvedPoll->assertOk();
        $approvedPoll->assertJson([
            'status'       => 'approved',
            'redirect_url' => route('two-factor.challenge'),
        ]);
    }

    public function test_workstation_quota_enforces_maximum_three_workstations_per_user(): void
    {
        $admin = User::factory()->create(['role' => 'CFO']);
        $user  = User::factory()->create(['role' => 'StaffAccountant']);

        // Create 3 already approved workstations
        for ($i = 1; $i <= 3; $i++) {
            UserWorkstation::create([
                'user_id'          => $user->id,
                'device_uuid'      => "ws-quota-{$i}",
                'workstation_name' => "Workstation {$i}",
                'status'           => UserWorkstation::STATUS_APPROVED,
                'approved_at'      => now(),
            ]);
        }

        // 4th pending request
        $fourthWorkstation = UserWorkstation::create([
            'user_id'          => $user->id,
            'device_uuid'      => 'ws-quota-4',
            'workstation_name' => 'Workstation 4 (Extra)',
            'status'           => UserWorkstation::STATUS_PENDING,
        ]);

        // Attempting to approve 4th workstation must fail
        $response = $this->actingAs($admin)
            ->post(route('user-security.workstations.approve', $fourthWorkstation));

        $response->assertSessionHasErrors('workstation');
        $this->assertFalse($fourthWorkstation->fresh()->isApproved());
    }

    public function test_super_admin_can_revoke_workstation(): void
    {
        $admin = User::factory()->create(['role' => 'CFO']);
        $user  = User::factory()->create(['role' => 'BillingClerk']);

        $workstation = UserWorkstation::create([
            'user_id'          => $user->id,
            'device_uuid'      => 'ws-revoke-test',
            'workstation_name' => 'Disused Terminal',
            'status'           => UserWorkstation::STATUS_APPROVED,
            'approved_at'      => now(),
        ]);

        $response = $this->actingAs($admin)
            ->delete(route('user-security.workstations.revoke', $workstation));

        $response->assertSessionHas('success');
        $this->assertTrue($workstation->fresh()->isRevoked());
    }

    public function test_single_active_session_displaces_previous_session_on_new_login(): void
    {
        $user = User::factory()->create([
            'email'                   => 'accountant@hospital.gov.ph',
            'role'                    => 'StaffAccountant',
            'password'                => Hash::make('Password123!'),
            'two_factor_confirmed_at' => now(),
        ]);

        $workstation = UserWorkstation::create([
            'user_id'          => $user->id,
            'device_uuid'      => 'ws-single-session-test',
            'workstation_name' => 'Accountant Desk 1',
            'status'           => UserWorkstation::STATUS_APPROVED,
            'approved_at'      => now(),
        ]);

        // 1. Session 1 logs in
        $session1Id = 'test-session-id-first-login-111111';
        $activeSession1 = UserActiveSession::create([
            'user_id'          => $user->id,
            'session_id'       => $session1Id,
            'workstation_id'   => $workstation->id,
            'device_name'      => 'Accountant Desk 1',
            'ip_address'       => '192.168.1.10',
            'login_at'         => now()->subMinutes(10),
            'last_activity_at' => now()->subMinutes(5),
            'is_terminated'    => false,
        ]);

        // 2. User logs in again from Session 2
        $session2Id = 'test-session-id-second-login-222222';
        $sessionManager = app(\App\Services\Security\ActiveSessionManagerService::class);
        $requestMock = request();
        $sessionManager->registerSession($user, $session2Id, $workstation, $requestMock);

        // Session 1 should be terminated with reason 'displaced_by_new_login'
        $this->assertTrue($activeSession1->fresh()->is_terminated);
        $this->assertSame(
            UserActiveSession::REASON_DISPLACED,
            $activeSession1->fresh()->termination_reason
        );

        // 3. When Session 1 makes any subsequent request, EnforceSingleActiveSession kicks it out
        $response = $this->actingAs($user)
            ->withSession([
                'auth.2fa_passed' => true,
                '_token'          => 'dummy-token',
            ]);

        // Simulate request having session ID 1
        session()->setId($session1Id);

        $testResponse = $this->get('/accounting/dashboard');
        $testResponse->assertRedirect(route('login'));
        $testResponse->assertSessionHas('displacement_warning');
        $this->assertGuest();
    }

    public function test_super_admin_can_force_terminate_active_session(): void
    {
        $admin = User::factory()->create(['role' => 'CFO']);
        $user  = User::factory()->create(['role' => 'Cashier']);

        $activeSession = UserActiveSession::create([
            'user_id'          => $user->id,
            'session_id'       => 'session-to-force-terminate-999',
            'device_name'      => 'POS Terminal 2',
            'ip_address'       => '192.168.1.20',
            'login_at'         => now(),
            'last_activity_at' => now(),
            'is_terminated'    => false,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('user-security.sessions.terminate', $activeSession));

        $response->assertSessionHas('success');
        $this->assertTrue($activeSession->fresh()->is_terminated);
        $this->assertSame(
            UserActiveSession::REASON_ADMIN_REVOKED,
            $activeSession->fresh()->termination_reason
        );
    }

    public function test_unauthorized_workstation_login_creates_pending_request_without_displacing_active_session_until_approved(): void
    {
        $admin = User::factory()->create([
            'role'                    => 'CFO',
            'two_factor_confirmed_at' => now(),
        ]);
        $user  = User::factory()->create([
            'email'                   => 'cashier@hospital.gov.ph',
            'role'                    => 'Cashier',
            'password'                => Hash::make('CashierPass123!'),
            'two_factor_confirmed_at' => now(),
        ]);

        // Workstation 1 is authorized and has an active live session
        $workstation1 = UserWorkstation::create([
            'user_id'          => $user->id,
            'device_uuid'      => 'ws-authorized-station-1',
            'workstation_name' => 'Main POS Counter',
            'status'           => UserWorkstation::STATUS_APPROVED,
            'approved_at'      => now(),
        ]);

        $session1Id = 'session-pos-terminal-1-active';
        $activeSession1 = UserActiveSession::create([
            'user_id'          => $user->id,
            'session_id'       => $session1Id,
            'workstation_id'   => $workstation1->id,
            'device_name'      => 'Main POS Counter',
            'ip_address'       => '192.168.1.50',
            'login_at'         => now()->subMinutes(30),
            'last_activity_at' => now()->subMinutes(1),
            'is_terminated'    => false,
        ]);

        // User attempts login from Workstation 2 (Unauthorized / Unbound device)
        $unauthorizedDeviceUuid = 'ws-unauthorized-new-laptop-999';
        $loginResponse = $this->withCookie(WorkstationBindingService::COOKIE_NAME, $unauthorizedDeviceUuid)
            ->post('/login', [
                'email'       => 'cashier@hospital.gov.ph',
                'password'    => 'CashierPass123!',
                'device_uuid' => $unauthorizedDeviceUuid,
            ]);

        // Workstation 2 MUST be redirected to workstation.pending holding page
        $loginResponse->assertRedirect(route('workstation.pending'));

        // Workstation 2 record must be created as PENDING
        $pendingWorkstation = UserWorkstation::where('device_uuid', $unauthorizedDeviceUuid)->first();
        $this->assertNotNull($pendingWorkstation);
        $this->assertSame(UserWorkstation::STATUS_PENDING, $pendingWorkstation->status);

        // Crucial: Active Session 1 must NOT be displaced yet while Workstation 2 is pending
        $this->assertFalse($activeSession1->fresh()->is_terminated);

        // Super Admin visits Workstation Security and sees the pending request
        $this->flushSession();
        $adminView = $this->actingAs($admin)
            ->withSession(['auth.2fa_passed' => true])
            ->get(route('user-security.workstations'));

        $adminView->assertOk();
        $adminView->assertSee('Real-Time Pending Workstation Requests');
        $adminView->assertSee($user->email);
        $adminView->assertSee($pendingWorkstation->workstation_name);

        // Super Admin approves the pending workstation
        $approveResponse = $this->actingAs($admin)
            ->post(route('user-security.workstations.approve', $pendingWorkstation));

        $approveResponse->assertSessionHas('success');
        $this->assertTrue($pendingWorkstation->fresh()->isApproved());

        // When approved Workstation 2 completes login / polling, it registers session 2
        $session2Id = 'session-laptop-terminal-2-approved';
        $sessionManager = app(\App\Services\Security\ActiveSessionManagerService::class);
        $sessionManager->registerSession($user, $session2Id, $pendingWorkstation->fresh(), request());

        // Now Session 1 IS displaced by the approved login
        $this->assertTrue($activeSession1->fresh()->is_terminated);
        $this->assertSame(UserActiveSession::REASON_DISPLACED, $activeSession1->fresh()->termination_reason);
    }

    public function test_super_admin_can_permanently_delete_workstation(): void
    {
        $admin = User::factory()->create(['role' => 'CFO']);
        $user  = User::factory()->create(['role' => 'BillingClerk']);

        $workstation = UserWorkstation::create([
            'user_id'          => $user->id,
            'device_uuid'      => 'ws-delete-test',
            'workstation_name' => 'Old Obsolete Terminal',
            'status'           => UserWorkstation::STATUS_REVOKED,
        ]);

        $response = $this->actingAs($admin)
            ->delete(route('user-security.workstations.destroy', $workstation));

        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('user_workstations', ['id' => $workstation->id]);
    }

    public function test_super_admin_can_reset_all_workstations(): void
    {
        $admin = User::factory()->create(['role' => 'CFO']);
        $user  = User::factory()->create(['role' => 'BillingClerk']);

        UserWorkstation::create([
            'user_id'          => $user->id,
            'device_uuid'      => 'ws-reset-1',
            'workstation_name' => 'Desk 1',
            'status'           => UserWorkstation::STATUS_APPROVED,
        ]);
        UserWorkstation::create([
            'user_id'          => $user->id,
            'device_uuid'      => 'ws-reset-2',
            'workstation_name' => 'Desk 2',
            'status'           => UserWorkstation::STATUS_PENDING,
        ]);

        $this->assertSame(2, UserWorkstation::count());

        $response = $this->actingAs($admin)
            ->post(route('user-security.workstations.reset-all'));

        $response->assertSessionHas('success');
        $this->assertSame(0, UserWorkstation::count());
    }

    public function test_bound_workstations_registry_paginates_at_five_per_page(): void
    {
        $admin = User::factory()->create(['role' => 'CFO']);
        $user  = User::factory()->create(['role' => 'BillingClerk']);

        // Create 8 approved workstations
        for ($i = 1; $i <= 8; $i++) {
            UserWorkstation::create([
                'user_id'          => $user->id,
                'device_uuid'      => "ws-paginate-{$i}",
                'workstation_name' => "Workstation {$i}",
                'status'           => UserWorkstation::STATUS_APPROVED,
                'last_seen_at'     => now()->subMinutes($i),
            ]);
        }

        $response = $this->actingAs($admin)->get(route('user-security.workstations'));

        $response->assertOk();
        $response->assertViewHas('boundWorkstations');

        $boundWorkstations = $response->viewData('boundWorkstations');
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $boundWorkstations);
        $this->assertSame(5, $boundWorkstations->perPage());
        $this->assertCount(5, $boundWorkstations->items());
        $this->assertSame(8, $boundWorkstations->total());
        $this->assertTrue($boundWorkstations->hasPages());
    }

    public function test_workstation_label_excludes_redundant_ip_and_detects_device_type(): void
    {
        $admin = User::factory()->create(['role' => 'CFO']);
        $user  = User::factory()->create([
            'email'    => 'nurse.laptop@hospital.gov.ph',
            'role'     => 'BillingClerk',
            'password' => Hash::make('HospitalSecure123!'),
        ]);

        $deviceUuid = 'ws-laptop-test-uuid-777';

        $response = $this->withCookie(WorkstationBindingService::COOKIE_NAME, $deviceUuid)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
            ])
            ->post('/login', [
                'email'       => 'nurse.laptop@hospital.gov.ph',
                'password'    => 'HospitalSecure123!',
                'device_uuid' => $deviceUuid,
                'device_type' => 'Laptop',
            ]);

        $response->assertRedirect(route('workstation.pending'));

        $workstation = UserWorkstation::where('device_uuid', $deviceUuid)->firstOrFail();

        // Label must be clean "Windows 10/11 (Google Chrome)" without "- 127.0.0.1"
        $this->assertSame('Windows 10/11 (Google Chrome)', $workstation->workstation_name);
        $this->assertStringNotContainsString((string) $workstation->ip_address, $workstation->workstation_name);
        $this->assertSame(UserWorkstation::DEVICE_LAPTOP, $workstation->device_type);
        $this->assertSame('Laptop', $workstation->deviceTypeLabel());
        $this->assertSame('ph-laptop', $workstation->deviceTypeIcon());

        // Legacy workstation_name with trailing IP is automatically stripped by cleanWorkstationName()
        $legacyWorkstation = UserWorkstation::create([
            'user_id'          => $user->id,
            'device_uuid'      => 'ws-mobile-legacy-888',
            'workstation_name' => 'Android Mobile (Google Chrome) - 209.35.167.177',
            'platform'         => 'Android Mobile',
            'browser'          => 'Google Chrome',
            'ip_address'       => '209.35.167.177',
            'status'           => UserWorkstation::STATUS_PENDING,
        ]);

        $this->assertSame('Android Mobile (Google Chrome)', $legacyWorkstation->cleanWorkstationName());
        $this->assertSame('Phone', $legacyWorkstation->deviceTypeLabel());
        $this->assertSame('ph-device-mobile', $legacyWorkstation->deviceTypeIcon());

        // Super Admin view renders clean name and hardware type badges (Laptop & Phone)
        $this->flushSession();
        $adminView = $this->actingAs($admin)
            ->withSession(['auth.2fa_passed' => true])
            ->get(route('user-security.workstations'));

        $adminView->assertOk();
        $adminView->assertSee('Windows 10/11 (Google Chrome)');
        $adminView->assertSee('Laptop');
        $adminView->assertSee('Android Mobile (Google Chrome)');
        $adminView->assertSee('Phone');
        $adminView->assertDontSee('Android Mobile (Google Chrome) - 209.35.167.177');
    }
}


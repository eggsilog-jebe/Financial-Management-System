<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\UserWorkstation;
use App\Services\Auth\TwoFactorRememberService;
use App\Services\Security\ActiveSessionManagerService;
use App\Services\Security\WorkstationBindingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

final class LoginController extends Controller
{
    public function __construct(
        private readonly TwoFactorRememberService     $twoFactorRememberService,
        private readonly WorkstationBindingService   $workstationService,
        private readonly ActiveSessionManagerService $sessionManager,
    ) {}

    /**
     * Show the Login Screen.
     */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an authentication attempt.
     */
    public function login(Request $request): RedirectResponse
    {
        // 1. Normalize email input (trim whitespace and convert to lowercase)
        $rawEmail        = (string) $request->input('email', '');
        $normalizedEmail = Str::lower(trim($rawEmail));
        $request->merge(['email' => $normalizedEmail]);

        // 2. Strict Input Validation (caps string lengths to prevent hashing DoS)
        $credentials = $request->validate([
            'email'    => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:128'],
        ]);

        // 3. Composite Rate Limiting (Account + IP Key)
        $throttleKey = Str::transliterate($normalizedEmail . '|' . $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            ActivityLog::logAuth(
                event:       'rate_limited',
                user:        null,
                description: "Brute-force protection: Rate limit exceeded for [{$normalizedEmail}] from IP [{$request->ip()}]. Locked for {$seconds} seconds.",
                ip:          $request->ip(),
                userAgent:   $request->userAgent()
            );

            return back()->withErrors([
                'email' => "Too many authentication attempts. Please try again in {$seconds} seconds.",
            ])->with('lockout_seconds', $seconds)->onlyInput('email');
        }

        // 4. Primary Credential Authentication Attempt
        if (Auth::attempt($credentials, false)) {
            RateLimiter::clear($throttleKey);
            Cache::forget('auth_streak:' . $throttleKey);

            $request->session()->regenerate();
            $request->session()->save();

            $user = Auth::user();

            // Block suspended accounts immediately
            if ($user->isSuspended()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                ActivityLog::logAuth(
                    event:       'login_blocked_suspended',
                    user:        $user,
                    description: "Suspended user [{$user->name}] attempted login.",
                    ip:          $request->ip(),
                    userAgent:   $request->userAgent()
                );

                return back()->withErrors([
                    'email' => 'This hospital user account has been suspended by an administrator. Please contact the CFO.',
                ])->onlyInput('email');
            }

            $user->update([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ]);

            // 5. Workstation / Computer Binding Verification
            $deviceUuid   = $this->workstationService->resolveDeviceUuid($request);
            $deviceCookie = $this->workstationService->createDeviceCookie($deviceUuid);
            $eval         = $this->workstationService->evaluateWorkstation($user, $deviceUuid, $request);

            // Fail-safe bootstrap: If this is a Super Admin logging into their very first workstation
            if ($eval['status'] === 'unrecognized' && $user->isSuperAdmin() && $user->approvedWorkstations()->count() === 0) {
                $bootstrapWorkstation = $this->workstationService->submitAuthorizationRequest($user, $deviceUuid, $request, 'Primary Super Admin Console');
                $bootstrapWorkstation->update([
                    'status'      => UserWorkstation::STATUS_APPROVED,
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                ]);
                $eval = [
                    'status'      => UserWorkstation::STATUS_APPROVED,
                    'workstation' => $bootstrapWorkstation,
                    'message'     => null,
                    'can_request' => false,
                ];
            }

            // Case A: Workstation was explicitly rejected by Super Admin
            if ($eval['status'] === UserWorkstation::STATUS_REJECTED) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => '🚫 Workstation Access Rejected: This computer has been rejected by the Super Administrator. Access denied.',
                ])->onlyInput('email')->withCookie($deviceCookie);
            }

            // Case B: Workstation was revoked
            if ($eval['status'] === UserWorkstation::STATUS_REVOKED) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => '🚫 Workstation Access Revoked: Authorization for this workstation was revoked by the Super Administrator. Please contact IT / CFO.',
                ])->onlyInput('email')->withCookie($deviceCookie);
            }

            // Case C: Unrecognized or Pending Workstation -> Route to holding screen
            if ($eval['status'] === UserWorkstation::STATUS_PENDING || $eval['status'] === 'unrecognized') {
                $workstation = $eval['workstation']
                    ?? $this->workstationService->submitAuthorizationRequest($user, $deviceUuid, $request);

                $request->session()->put('auth.pending_workstation_id', $workstation->id);
                $request->session()->put('auth.pending_device_uuid', $deviceUuid);

                return redirect()->route('workstation.pending')
                    ->withCookie($deviceCookie)
                    ->with('info', '📡 New workstation detected. Authorization request sent in real-time to Super Administrator.');
            }

            // Case D: Workstation is APPROVED
            /** @var UserWorkstation $workstation */
            $workstation = $eval['workstation'];
            $request->session()->put('auth.workstation_id', $workstation->id);
            $request->session()->put('auth.device_uuid', $deviceUuid);

            // 6. Single Active Session Enforcement: Terminates previous sessions immediately
            $this->sessionManager->registerSession($user, $request->session()->getId(), $workstation, $request);

            $request->session()->put('auth.last_activity_at', now()->toIso8601String());

            ActivityLog::logAuth(
                event:       'login',
                user:        $user,
                description: "User [{$user->name}] ({$user->role}) logged in from authorized workstation [{$workstation->workstation_name}].",
                ip:          $request->ip(),
                userAgent:   $request->userAgent()
            );

            // 7. Second Factor Requirement (TOTP)
            $request->session()->put('auth.2fa_passed', false);

            if ($user->hasTwoFactorEnabled()) {
                return redirect()->route('two-factor.challenge')->withCookie($deviceCookie);
            }

            return redirect()->route('two-factor.setup')
                ->withCookie($deviceCookie)
                ->with('info', '🔐 For your hospital account security, please set up Google Authenticator before continuing.');
        }

        // 8. Failed Credential Handling with Progressive Exponential Backoff
        $streakKey     = 'auth_streak:' . $throttleKey;
        $failureStreak = (int) Cache::get($streakKey, 0) + 1;
        Cache::put($streakKey, $failureStreak, now()->addHour());

        // Progressive decay duration:
        // - 1-5 failures: 60 seconds (1 minute window)
        // - 6-7 failures: 300 seconds (5 minutes cooldown)
        // - 8+ failures: 900 seconds (15 minutes cooldown)
        $decaySeconds = match (true) {
            $failureStreak >= 8 => 900,
            $failureStreak > 5  => 300,
            default             => 60,
        };

        RateLimiter::hit($throttleKey, $decaySeconds);

        // If in escalated failure streak, immediately enforce lockout threshold
        if ($failureStreak > 5) {
            while (RateLimiter::attempts($throttleKey) < 5) {
                RateLimiter::hit($throttleKey, $decaySeconds);
            }
        }

        ActivityLog::logAuth(
            event:       'failed_login',
            user:        null,
            description: "Failed login attempt for email [{$credentials['email']}]. Recorded consecutive failure streak [{$failureStreak}].",
            ip:          $request->ip(),
            userAgent:   $request->userAgent()
        );

        return back()->withErrors([
            'email' => 'The provided credentials do not match our registered hospital records.',
        ])->onlyInput('email');
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $isIdle = $request->input('reason') === 'idle' || $request->query('reason') === 'idle';

        if ($user) {
            $sessionId = $request->session()->getId();
            $reason = $isIdle
                ? \App\Models\UserActiveSession::REASON_IDLE_TIMEOUT
                : \App\Models\UserActiveSession::REASON_MANUAL_LOGOUT;

            $this->sessionManager->terminateCurrentSession($sessionId, $reason);

            ActivityLog::logAuth(
                event:       $isIdle ? 'idle_timeout_logout' : 'logout',
                user:        $user,
                description: $isIdle
                    ? "User [{$user->name}] ({$user->role}) logged out due to inactivity."
                    : "User [{$user->name}] ({$user->role}) logged out.",
                ip:          $request->ip(),
                userAgent:   $request->userAgent()
            );
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $response = redirect()->route('login');

        if ($isIdle) {
            $response->with('session_expired', 'Your session expired due to inactivity (3 minutes). Please sign in again.');
        }

        if ($user) {
            $response->withCookie($this->twoFactorRememberService->forgetCookie($user));
        }

        return $response;
    }
}

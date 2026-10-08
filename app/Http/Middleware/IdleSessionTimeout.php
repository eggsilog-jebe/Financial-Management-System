<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Server-side idle session timeout guard.
 *
 * On every authenticated request, this middleware:
 * 1. Records the timestamp of the last activity in the session.
 * 2. Checks if the user has been idle longer than the configured threshold.
 * 3. If so, logs the timeout event and forces a logout.
 *
 * This acts as a hard server-side enforcement independent of the client-side
 * idle-monitor.js. Even if JS is disabled, the session will expire.
 */
final class IdleSessionTimeout
{
    /** Idle threshold in seconds (3 minutes = 180 seconds). */
    public const IDLE_THRESHOLD_SECONDS = 180;

    /**
     * Routes that should never trigger the idle check (e.g. heartbeat, logout, 2FA).
     *
     * @var string[]
     */
    private const EXEMPT_ROUTES = [
        'logout',
        'logout.get',
        'session.heartbeat',
        'login',
        'login.post',
        'two-factor.challenge',
        'two-factor.challenge.verify',
        'two-factor.email-otp.send',
        'two-factor.setup',
        'two-factor.setup.store',
        'two-factor.setup.confirm',
        'two-factor.setup.destroy',
    ];

    public function __construct(
        private readonly ?\App\Services\Security\ActiveSessionManagerService $sessionManager = null,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Only applies to authenticated users
        if (! Auth::check()) {
            return $next($request);
        }

        // Skip exempt routes (heartbeat, logout, login, 2FA flows)
        if ($request->routeIs(...self::EXEMPT_ROUTES)) {
            return $next($request);
        }

        $lastActivity = $request->session()->get('auth.last_activity_at');
        $idleThreshold = (int) config('session.idle_timeout', self::IDLE_THRESHOLD_SECONDS);

        if ($lastActivity !== null) {
            $lastActivityCarbon = \Illuminate\Support\Carbon::parse($lastActivity);
            $idleSeconds = max(0, now()->getTimestamp() - $lastActivityCarbon->getTimestamp());

            if ($idleSeconds > $idleThreshold) {
                $user = Auth::user();

                if ($user) {
                    ActivityLog::logAuth(
                        event:       'idle_timeout_logout',
                        user:        $user,
                        description: "User [{$user->name}] ({$user->role}) was automatically logged out after {$idleSeconds}s of inactivity.",
                        ip:          $request->ip(),
                        userAgent:   $request->userAgent(),
                    );

                    if ($this->sessionManager !== null) {
                        $this->sessionManager->terminateCurrentSession(
                            $request->session()->getId(),
                            \App\Models\UserActiveSession::REASON_IDLE_TIMEOUT
                        );
                    }
                }

                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->expectsJson()) {
                    return response()->json([
                        'message'      => 'Session expired due to inactivity.',
                        'redirect_url' => route('login', ['expired' => 1]),
                    ], 401);
                }

                return redirect()->route('login')
                    ->with('session_expired', 'Your session expired due to inactivity (3 minutes). Please sign in again.');
            }
        }

        // Refresh the last activity timestamp on every active request
        $request->session()->put('auth.last_activity_at', now()->toIso8601String());

        return $next($request);
    }
}

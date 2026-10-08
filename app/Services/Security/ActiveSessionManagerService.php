<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\UserActiveSession;
use App\Models\UserWorkstation;
use App\Services\Cache\L1InMemoryCache;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Service managing enterprise single active session enforcement and live session tracking.
 *
 * Requirements:
 * - Single active session enforcement: if another user or workstation logs into the same account,
 *   terminate previous session immediately with a displacement security warning.
 * - Super Admin can view active sessions and force-terminate them in real-time.
 */
final class ActiveSessionManagerService
{
    public function __construct(
        private readonly L1InMemoryCache $l1,
    ) {}
    /**
     * Register a new active session upon successful login/verification.
     * Enforces Single Active Session: instantly terminates any existing sessions for this user.
     */
    public function registerSession(
        User $user,
        string $sessionId,
        ?UserWorkstation $workstation,
        Request $request
    ): UserActiveSession {
        // 1. Terminate all other active sessions for this user (Displacement Rule)
        $previousActiveSessions = UserActiveSession::where('user_id', $user->id)
            ->where('is_terminated', false)
            ->where('session_id', '!=', $sessionId)
            ->get();

        foreach ($previousActiveSessions as $oldSession) {
            $oldSession->terminate(UserActiveSession::REASON_DISPLACED);
            Cache::put("session:terminated:{$oldSession->session_id}", UserActiveSession::REASON_DISPLACED, 3600);

            ActivityLog::logAuth(
                event:       'session_displaced',
                user:        $user,
                description: "Session [{$oldSession->session_id}] for user [{$user->name}] was terminated due to a new login from IP [{$request->ip()}].",
                ip:          $request->ip(),
                userAgent:   $request->userAgent()
            );
        }

        // Fast-path Redis registrations for instantaneous validation
        Cache::put("user:active_session:{$user->id}", $sessionId, 86400);
        Cache::forget("session:terminated:{$sessionId}");

        // 2. Create or reactivate the current session
        /** @var UserActiveSession $activeSession */
        $activeSession = UserActiveSession::updateOrCreate(
            ['session_id' => $sessionId],
            [
                'user_id'            => $user->id,
                'workstation_id'     => $workstation?->id,
                'device_uuid'        => $workstation?->device_uuid,
                'device_name'        => $workstation?->workstation_name ?: $request->userAgent(),
                'ip_address'         => $request->ip(),
                'user_agent'         => $request->userAgent(),
                'login_at'           => now(),
                'last_activity_at'   => now(),
                'is_terminated'      => false,
                'termination_reason' => null,
                'terminated_at'      => null,
            ]
        );

        return $activeSession;
    }

    /**
     * Update the last activity timestamp for an active session.
     */
    public function touchSession(string $sessionId): void
    {
        UserActiveSession::where('session_id', $sessionId)
            ->where('is_terminated', false)
            ->update(['last_activity_at' => now()]);
    }

    /**
     * Check if a session has been terminated or displaced.
     * Executes in a single query to eliminate latency on cross-network database connections.
     *
     * @return string|null Reason string if terminated/displaced, null if still active and valid.
     */
    public function checkSessionDisplacement(string $sessionId, User $user): ?string
    {
        // 0. Layer 1 Check: Instant process in-memory validation (0ms, zero network hops)
        $l1Status = $this->l1->get("session:valid:{$sessionId}");
        if ($l1Status === true) {
            return null; // Confirmed valid within short L1 TTL
        }

        // 1. Fast Path: Check if this session was explicitly marked terminated in Redis/Distributed cache
        $cachedReason = Cache::get("session:terminated:{$sessionId}");
        if ($cachedReason !== null) {
            $this->l1->put("session:terminated:{$sessionId}", $cachedReason, 60);
            return (string) $cachedReason;
        }

        // 2. Fast Path: Check if this session is the current active session in Redis/Distributed cache
        $activeSessionId = Cache::get("user:active_session:{$user->id}");
        if ($activeSessionId === $sessionId) {
            // Warm L1 in-memory cache for 30s
            $this->l1->put("session:valid:{$sessionId}", true, 30);
            return null; // Instant sub-millisecond in-memory validation
        }

        // 3. Fallback: Query database with high-cardinality composite index
        $sessions = UserActiveSession::select(['id', 'session_id', 'user_id', 'login_at', 'is_terminated', 'termination_reason'])
            ->where('user_id', $user->id)
            ->where(function ($q) use ($sessionId): void {
                $q->where('session_id', $sessionId)
                    ->orWhere('is_terminated', false);
            })
            ->orderByDesc('login_at')
            ->get();

        /** @var UserActiveSession|null $record */
        $record = $sessions->firstWhere('session_id', $sessionId);

        // If explicitly marked as terminated, cache and return the reason
        if ($record && $record->is_terminated) {
            $reason = $record->termination_reason ?: UserActiveSession::REASON_DISPLACED;
            Cache::put("session:terminated:{$sessionId}", $reason, 3600);
            $this->l1->put("session:terminated:{$sessionId}", $reason, 60);
            return $reason;
        }

        // Check if there is another newer active session for this user (displacement guard)
        $threshold = $record?->login_at ?? now()->subDay();
        $newerSession = $sessions->first(function (UserActiveSession $s) use ($sessionId, $threshold): bool {
            return ! $s->is_terminated
                && $s->session_id !== $sessionId
                && $s->login_at > $threshold;
        });

        if ($newerSession !== null) {
            if ($record) {
                $record->terminate(UserActiveSession::REASON_DISPLACED);
            }
            Cache::put("session:terminated:{$sessionId}", UserActiveSession::REASON_DISPLACED, 3600);
            $this->l1->put("session:terminated:{$sessionId}", UserActiveSession::REASON_DISPLACED, 60);
            return UserActiveSession::REASON_DISPLACED;
        }

        // Warm up active session in L2 (with jitter) and L1 for fast future queries
        if ($record && ! $record->is_terminated) {
            $ttlWithJitter = 86400 + random_int(0, 300);
            Cache::put("user:active_session:{$user->id}", $sessionId, $ttlWithJitter);
            $this->l1->put("session:valid:{$sessionId}", true, 30);
        }

        return null;
    }

    /**
     * Force-terminate an active session by Super Admin.
     */
    public function terminateSession(
        UserActiveSession $session,
        User $admin,
        string $reason = UserActiveSession::REASON_ADMIN_REVOKED
    ): void {
        $session->terminate($reason);
        Cache::put("session:terminated:{$session->session_id}", $reason, 3600);
        Cache::forget("user:active_session:{$session->user_id}");
        $this->l1->forget("session:valid:{$session->session_id}");
        $this->l1->put("session:terminated:{$session->session_id}", $reason, 60);

        ActivityLog::logAuth(
            event:       'session_force_terminated',
            user:        $session->user,
            description: "Super Admin [{$admin->name}] force-terminated session [{$session->session_id}] for user [{$session->user?->name}]. Reason: {$reason}",
            ip:          request()->ip(),
            userAgent:   request()->userAgent()
        );
    }

    /**
     * Terminate the session on explicit user logout or idle timeout.
     */
    public function terminateCurrentSession(string $sessionId, string $reason = UserActiveSession::REASON_MANUAL_LOGOUT): void
    {
        Cache::put("session:terminated:{$sessionId}", $reason, 3600);
        $this->l1->forget("session:valid:{$sessionId}");

        $this->terminateSessions(
            UserActiveSession::where('session_id', $sessionId)->where('is_terminated', false)->get(),
            $reason
        );
    }

    /**
     * Terminate a set of sessions and invalidate every cache layer so the
     * middleware rejects them on the very next request.
     *
     * @param  iterable<int, UserActiveSession>  $sessions
     */
    public function terminateSessions(iterable $sessions, string $reason): int
    {
        $count = 0;

        foreach ($sessions as $session) {
            if ($session->is_terminated) {
                continue;
            }

            $session->terminate($reason);
            Cache::put("session:terminated:{$session->session_id}", $reason, 3600);
            Cache::forget("user:active_session:{$session->user_id}");
            $this->l1->forget("session:valid:{$session->session_id}");
            $this->l1->put("session:terminated:{$session->session_id}", $reason, 60);
            $count++;
        }

        return $count;
    }

    /**
     * Retrieve all active live sessions across the system.
     *
     * @return Collection<int, UserActiveSession>
     */
    public function getActiveSessions(): Collection
    {
        return UserActiveSession::with(['user', 'workstation'])
            ->active()
            ->orderByDesc('last_activity_at')
            ->get();
    }
}

/**
 * idle-monitor.js — Hospital FMS Silent Idle Inactivity & Mouse Activity Tracker
 *
 * Tracks mouse movements, clicks, scrolls, and keystrokes to detect user inactivity.
 * - Enforces a strict 3-minute (180s) inactivity limit.
 * - Tracks genuine mouse movements (evaluating X/Y coordinate displacement).
 * - Tracks mouse clicks, wheel scrolling, touch interactions, and keystrokes.
 * - Continuously evaluates idle duration on a 1-second interval loop.
 * - Immediately checks on window focus / tab visibility change (preventing background tab throttling delays).
 * - Synchronizes across multiple browser tabs via localStorage.
 * - Refreshes server session via /session/heartbeat (touch: true, throttled to 30s).
 * - Passively checks displacement & multi-tab state every 15s (touch: false).
 * - NO warning modal or countdown is shown before logout.
 * - After 3 minutes of zero mouse/user activity, immediately force-terminates the session
 *   and redirects to the login page where the explanatory modal is presented.
 */
(function () {
  'use strict';

  // ── Configuration ──────────────────────────────────────────────────────────
  const IDLE_TIMEOUT_MS            = 3 * 60 * 1000; // 3 minutes = 180,000 ms
  const ACTIVITY_TOUCH_INTERVAL_MS = 30 * 1000;     // Throttled server touch ping (max once per 30s during continuous activity)
  const STATUS_SYNC_INTERVAL_MS    = 15 * 1000;     // Passive check every 15s
  const STORAGE_KEY                = 'fms_last_activity_time';

  // CSRF token from document meta tag
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

  // ── State ──────────────────────────────────────────────────────────────────
  let lastActivityTime = Date.now();
  let lastMouseX       = null;
  let lastMouseY       = null;
  let lastTouchPing    = 0;
  let isLoggingOut     = false;
  let tickerInterval   = null;
  let syncInterval     = null;
  let activityDebounce = null;

  // Initialize stored activity timestamp
  try {
    const existingStored = parseInt(localStorage.getItem(STORAGE_KEY) || '0', 10);
    if (!existingStored || isNaN(existingStored) || existingStored < Date.now() - IDLE_TIMEOUT_MS) {
      localStorage.setItem(STORAGE_KEY, String(Date.now()));
    } else {
      lastActivityTime = existingStored;
    }
  } catch (_) {}

  // ── Force Logout ───────────────────────────────────────────────────────────
  function forceLogout() {
    if (isLoggingOut) return;
    isLoggingOut = true;

    // Clear background timers
    if (tickerInterval) clearInterval(tickerInterval);
    if (syncInterval) clearInterval(syncInterval);

    try {
      localStorage.removeItem(STORAGE_KEY);
      sessionStorage.removeItem('idle_modal_dismissed');
    } catch (_) {}

    // Hide any navigation loaders
    if (typeof window.hideNavigationLoader === 'function') {
      try {
        window.hideNavigationLoader();
      } catch (_) {}
    }

    console.warn('[FMS Idle Monitor] 3 minutes of inactivity reached without mouse/user activity. Forcefully terminating session...');

    // Redirect to logout endpoint with idle reason
    try {
      window.location.replace('/logout?reason=idle');
    } catch (_) {
      window.location.href = '/logout?reason=idle';
    }

    // Safety fallback directly to login if navigation is delayed
    setTimeout(() => {
      window.location.replace('/login?reason=idle&expired=1');
    }, 1200);
  }

  // ── Activity Registration ──────────────────────────────────────────────────
  function recordUserActivity(source = 'activity') {
    if (isLoggingOut) return;

    const now = Date.now();
    lastActivityTime = now;

    try {
      localStorage.setItem(STORAGE_KEY, String(now));
    } catch (_) {}

    // Debounce server touch ping so we don't overwhelm network on every mousemove
    if (!activityDebounce) {
      activityDebounce = setTimeout(() => {
        activityDebounce = null;
        sendHeartbeat({ touch: true });
      }, 500);
    }
  }

  // Mouse move handler (requires genuine displacement to avoid synthetic jitter)
  function onMouseMove(e) {
    if (lastMouseX === null || lastMouseY === null) {
      lastMouseX = e.clientX;
      lastMouseY = e.clientY;
      recordUserActivity('mouse_first');
      return;
    }

    // Check if mouse actually moved
    if (e.clientX !== lastMouseX || e.clientY !== lastMouseY) {
      lastMouseX = e.clientX;
      lastMouseY = e.clientY;
      recordUserActivity('mouse_move');
    }
  }

  // ── Inactivity Evaluator (Runs every 1 second) ───────────────────────────────
  function evaluateInactivity() {
    if (isLoggingOut) return;

    const now = Date.now();
    let storedTime = 0;
    try {
      storedTime = parseInt(localStorage.getItem(STORAGE_KEY) || '0', 10);
    } catch (_) {}

    // Most recent interaction across current tab or other tabs
    const effectiveLastActivity = Math.max(lastActivityTime, isNaN(storedTime) ? 0 : storedTime);
    const idleElapsedMs = now - effectiveLastActivity;

    // Check if 3 minutes (180,000 ms) have passed without activity
    if (idleElapsedMs >= IDLE_TIMEOUT_MS) {
      forceLogout();
    }
  }

  // ── Server Heartbeat & Sync ────────────────────────────────────────────────
  function sendHeartbeat(options = {}) {
    if (isLoggingOut) return;

    const isTouch = Boolean(options.touch);
    const force   = Boolean(options.force);
    const now     = Date.now();

    // Throttle user-activity touches to at most once per ACTIVITY_TOUCH_INTERVAL_MS
    if (isTouch && !force && (now - lastTouchPing < ACTIVITY_TOUCH_INTERVAL_MS)) {
      return;
    }

    if (isTouch) {
      lastTouchPing = now;
    }

    fetch('/session/heartbeat', {
      method: 'POST',
      headers: {
        'Content-Type':     'application/json',
        'X-CSRF-TOKEN':     csrfToken,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept':           'application/json',
      },
      credentials: 'same-origin',
      body: JSON.stringify({
        touch: isTouch,
      }),
    })
      .then(response => {
        if (response.status === 401) {
          // Session expired or displaced on server
          return response.json().then(data => {
            window.location.replace(data.redirect_url || '/login?reason=idle&expired=1');
          }).catch(() => {
            window.location.replace('/login?reason=idle&expired=1');
          });
        }

        if (response.ok) {
          return response.json().then(data => {
            if (data && typeof data.remaining === 'number') {
              if (data.remaining <= 0) {
                forceLogout();
              }
            }
          });
        }
      })
      .catch(() => {
        // Silently swallow network dropouts
      });
  }

  // ── Event Listeners ────────────────────────────────────────────────────────
  // Track mouse interactions
  window.addEventListener('mousemove', onMouseMove, { passive: true });
  window.addEventListener('mousedown', () => recordUserActivity('mouse_down'), { passive: true });
  window.addEventListener('mouseup', () => recordUserActivity('mouse_up'), { passive: true });
  window.addEventListener('click', () => recordUserActivity('click'), { passive: true });
  window.addEventListener('dblclick', () => recordUserActivity('dblclick'), { passive: true });
  window.addEventListener('contextmenu', () => recordUserActivity('contextmenu'), { passive: true });
  window.addEventListener('wheel', () => recordUserActivity('wheel'), { passive: true });

  // Track keyboard & touch
  window.addEventListener('keydown', () => recordUserActivity('keydown'), { passive: true });
  window.addEventListener('keyup', () => recordUserActivity('keyup'), { passive: true });
  window.addEventListener('touchstart', () => recordUserActivity('touchstart'), { passive: true });
  window.addEventListener('touchmove', () => recordUserActivity('touchmove'), { passive: true });
  window.addEventListener('scroll', () => recordUserActivity('scroll'), { passive: true });

  // Tab visibility & Window focus (ensures immediate evaluation when switching back to tab)
  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
      evaluateInactivity();
    }
  });
  window.addEventListener('focus', evaluateInactivity);
  window.addEventListener('pageshow', evaluateInactivity);

  // Synchronize across multiple browser tabs
  window.addEventListener('storage', (e) => {
    if (e.key === STORAGE_KEY) {
      if (!e.newValue) {
        // Another tab logged out
        forceLogout();
      } else {
        const remoteTime = parseInt(e.newValue, 10);
        if (!isNaN(remoteTime)) {
          lastActivityTime = Math.max(lastActivityTime, remoteTime);
        }
      }
    }
  });

  // ── Active Background Loops ────────────────────────────────────────────────
  // High-frequency 1-second evaluator loop: detects when 3 minutes elapse
  tickerInterval = setInterval(evaluateInactivity, 1000);

  // Passive server sync loop: every 15s checks server-side session status
  syncInterval = setInterval(() => {
    if (!isLoggingOut) {
      sendHeartbeat({ touch: false });
    }
  }, STATUS_SYNC_INTERVAL_MS);

  // ── Developer & Diagnostics API ────────────────────────────────────────────
  window.FmsIdleMonitor = {
    getIdleSeconds: () => {
      const stored = parseInt(localStorage.getItem(STORAGE_KEY) || '0', 10);
      const effective = Math.max(lastActivityTime, isNaN(stored) ? 0 : stored);
      return Math.floor((Date.now() - effective) / 1000);
    },
    getLastActivity: () => new Date(lastActivityTime).toLocaleTimeString(),
    simulateIdleTimeout: () => {
      console.log('[FMS Idle Monitor] Simulating 3-minute idle timeout...');
      forceLogout();
    },
  };

  console.info('[FMS Security] Silent 3-minute idle monitor active. Tracking mouse and keyboard interaction.');
})();

/**
 * idle-monitor.js — Hospital FMS Idle Session Timeout Controller
 *
 * Tracks user activity and enforces a 3-minute (180s) idle timeout.
 * - At T-60s remaining: triggers warning countdown modal (60s countdown)
 * - At T-0: submits a logout POST to invalidate the server session
 * - On active user interaction: pings /session/heartbeat (with touch: true, max once per 30s)
 * - Periodic background check: every 10s checks displacement and syncs remaining seconds (with touch: false)
 * - Stay Logged In button: immediately pings /session/heartbeat with touch: true and resets 3-minute timer
 *
 * Server-side IdleSessionTimeout middleware is the hard enforcement mechanism (180s).
 */
(function () {
  'use strict';

  // ── Configuration ──────────────────────────────────────────────────────────
  const IDLE_TIMEOUT_MS            = 3 * 60 * 1000; // 3 minutes = 180,000 ms
  const WARNING_BEFORE_MS          = 60 * 1000;     // 60 seconds warning before expiry
  const ACTIVITY_TOUCH_INTERVAL_MS = 30 * 1000;     // Send touch ping at most once per 30s during continuous activity
  const STATUS_SYNC_INTERVAL_MS    = 10 * 1000;     // Check displacement/sync passively every 10s

  // Read CSRF token from meta tag
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

  // ── State ──────────────────────────────────────────────────────────────────
  let idleTimer      = null;
  let warningTimer   = null;
  let countdownTimer = null;
  let lastTouchPing  = 0;
  let warningShown   = false;
  let isLoggingOut   = false;

  // ── DOM References (injected by layouts/app.blade.php) ───────────────────────
  const modal       = document.getElementById('idleTimeoutModal');
  const countdownEl = document.getElementById('idle-countdown-seconds');
  const stayBtn     = document.getElementById('idle-stay-logged-in');
  const logoutBtn   = document.getElementById('idle-logout-now');
  const logoutForm  = document.getElementById('idle-logout-form');

  if (! modal) return; // Guard: only execute on authenticated pages containing the modal

  // ── Heartbeat & Displacement Detection ──────────────────────────────────────
  function sendHeartbeat(options = {}) {
    const isTouch = Boolean(options.touch);
    const force   = Boolean(options.force);
    const now     = Date.now();

    // Throttle user-activity touches to at most once per ACTIVITY_TOUCH_INTERVAL_MS unless forced
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
          // Displaced by concurrent login or session expired on server
          return response.json().then(data => {
            window.location.href = data.redirect_url || '/login?expired=1';
          }).catch(() => {
            window.location.href = '/login?expired=1';
          });
        }

        if (response.ok) {
          return response.json().then(data => {
            handleServerSync(data);
          });
        }
      })
      .catch(() => {
        // Silently swallow network dropouts — client timers and server middleware will enforce expiry
      });
  }

  // Handle server sync response (supports multi-tab synchronization)
  function handleServerSync(data) {
    if (!data || typeof data.remaining !== 'number') return;

    const remainingSec = data.remaining;

    // If server says we have <= 60 seconds remaining and modal not shown, display warning
    if (remainingSec <= 60 && !warningShown) {
      showWarningModal(remainingSec);
    } else if (remainingSec > 60 && warningShown) {
      // User performed an action in another tab! Dismiss warning modal and sync local timer
      hideWarningModal();
      resetIdleTimer(remainingSec * 1000);
    }
  }

  // Periodic passive check every 10 seconds (detects displacement & multi-tab state without resetting activity)
  setInterval(() => {
    if (!isLoggingOut) {
      sendHeartbeat({ touch: false });
    }
  }, STATUS_SYNC_INTERVAL_MS);

  // ── Countdown Display ──────────────────────────────────────────────────────
  function startCountdown(durationSeconds) {
    stopCountdown();
    let remaining = Math.max(0, Math.round(durationSeconds));

    const updateDisplay = () => {
      if (countdownEl) {
        countdownEl.textContent = String(remaining);
      }
    };

    updateDisplay();

    countdownTimer = setInterval(() => {
      remaining -= 1;
      updateDisplay();

      if (remaining <= 0) {
        stopCountdown();
        forceLogout();
      }
    }, 1000);
  }

  function stopCountdown() {
    if (countdownTimer) {
      clearInterval(countdownTimer);
      countdownTimer = null;
    }
  }

  // ── Warning Modal ──────────────────────────────────────────────────────────
  function showWarningModal(secondsLeft = 60) {
    if (warningShown) return;
    warningShown = true;

    // Trigger Alpine.js reactive display
    window.dispatchEvent(new CustomEvent('open-idle-modal'));

    // Start 1-second interval countdown
    startCountdown(secondsLeft);
  }

  function hideWarningModal() {
    if (!warningShown) return;
    warningShown = false;

    stopCountdown();

    // Trigger Alpine.js reactive hide
    window.dispatchEvent(new CustomEvent('close-idle-modal'));
  }

  // ── Force Logout ───────────────────────────────────────────────────────────
  function forceLogout() {
    if (isLoggingOut) return;
    isLoggingOut = true;

    hideWarningModal();

    if (logoutForm) {
      // Ensure hidden input reason=idle is present
      let reasonInput = logoutForm.querySelector('input[name="reason"]');
      if (!reasonInput) {
        reasonInput = document.createElement('input');
        reasonInput.type = 'hidden';
        reasonInput.name = 'reason';
        reasonInput.value = 'idle';
        logoutForm.appendChild(reasonInput);
      }
      logoutForm.submit();
    } else {
      window.location.href = '/logout?reason=idle';
    }

    // Safety fallback if form submission hangs
    setTimeout(() => {
      window.location.href = '/login?session_expired=1';
    }, 2500);
  }

  // ── Reset Idle Timer ───────────────────────────────────────────────────────
  function resetIdleTimer(overrideDurationMs = null) {
    clearTimeout(idleTimer);
    clearTimeout(warningTimer);

    const totalDuration = overrideDurationMs ?? IDLE_TIMEOUT_MS;
    const warningDelay  = Math.max(0, totalDuration - WARNING_BEFORE_MS);

    // Schedule warning modal (at 60s remaining)
    warningTimer = setTimeout(() => {
      showWarningModal(60);
    }, warningDelay);

    // Schedule automatic logout at exact expiration
    idleTimer = setTimeout(() => {
      forceLogout();
    }, totalDuration);
  }

  // ── Activity Event Listeners ───────────────────────────────────────────────
  const ACTIVITY_EVENTS = ['mousemove', 'keydown', 'mousedown', 'touchstart', 'scroll', 'click'];
  let activityDebounce = null;

  function onActivity() {
    // If warning modal is already shown, do NOT passively dismiss with mousemove —
    // user must deliberately click "I'm Still Here"
    if (warningShown) return;

    if (activityDebounce) return;
    activityDebounce = setTimeout(() => {
      activityDebounce = null;
      sendHeartbeat({ touch: true });
      resetIdleTimer();
    }, 500);
  }

  ACTIVITY_EVENTS.forEach(event => {
    document.addEventListener(event, onActivity, { passive: true });
  });

  // ── Stay Logged In Button ──────────────────────────────────────────────────
  if (stayBtn) {
    stayBtn.addEventListener('click', (e) => {
      e.preventDefault();
      // Force immediate server session touch
      sendHeartbeat({ touch: true, force: true });
      hideWarningModal();
      resetIdleTimer();

      if (typeof window.showToast === 'function') {
        window.showToast('Session renewed. Inactivity timer reset.', 'info');
      }
    });
  }

  // ── Logout Now Button ──────────────────────────────────────────────────────
  if (logoutBtn) {
    logoutBtn.addEventListener('click', (e) => {
      e.preventDefault();
      forceLogout();
    });
  }

  // ── Init on page load ──────────────────────────────────────────────────────
  resetIdleTimer();
})();

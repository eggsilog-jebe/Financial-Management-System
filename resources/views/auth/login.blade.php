<!doctype html>
<html lang="en" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign in &bull; Hospital Financial Management System (FMS)</title>
  <x-favicon />

  <!-- Google Fonts: Plus Jakarta Sans, Inter & JetBrains Mono -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Immediate Theme Boot (Default: Light, Synced with fms_theme & colorScheme) -->
  <script>
    (function() {
      const savedTheme = localStorage.getItem('fms_theme');
      if (savedTheme === 'dark') {
        document.documentElement.classList.add('dark');
        document.documentElement.style.colorScheme = 'dark';
      } else {
        document.documentElement.classList.remove('dark');
        document.documentElement.style.colorScheme = 'light';
      }
    })();
  </script>

  <!-- Tailwind CSS v4 & Alpine.js Asset Bundle -->
  @vite(['resources/css/app.css', 'resources/js/app.js'])

  <style>
    /* ─── Browser Autofill Fix (Eliminates ugly gray/slate autofill background) ─── */
    input:-webkit-autofill,
    input:-webkit-autofill:hover, 
    input:-webkit-autofill:focus, 
    input:-webkit-autofill:active {
      -webkit-box-shadow: 0 0 0 1000px #ffffff inset !important;
      -webkit-text-fill-color: #0f172a !important;
      box-shadow: 0 0 0 1000px #ffffff inset !important;
      caret-color: #0f172a !important;
      color: #0f172a !important;
      transition: background-color 5000s ease-in-out 0s, color 5000s ease-in-out 0s !important;
    }

    html.dark input:-webkit-autofill,
    html.dark input:-webkit-autofill:hover, 
    html.dark input:-webkit-autofill:focus, 
    html.dark input:-webkit-autofill:active {
      -webkit-box-shadow: 0 0 0 1000px #0f172a inset !important;
      -webkit-text-fill-color: #f8fafc !important;
      box-shadow: 0 0 0 1000px #0f172a inset !important;
      caret-color: #f8fafc !important;
      color: #f8fafc !important;
      transition: background-color 5000s ease-in-out 0s, color 5000s ease-in-out 0s !important;
    }

    input:-webkit-autofill:focus {
      border-color: #059669 !important;
      box-shadow: 0 0 0 1000px #ffffff inset, 0 0 0 2px rgba(5, 150, 105, 0.25) !important;
    }

    html.dark input:-webkit-autofill:focus {
      border-color: #10b981 !important;
      box-shadow: 0 0 0 1000px #0f172a inset, 0 0 0 2px rgba(16, 185, 129, 0.3) !important;
    }

  </style>
</head>
@php
  $lockoutSeconds = (int) (session('lockout_seconds') ?? 0);
  if ($lockoutSeconds === 0 && $errors->has('email')) {
    $firstEmailError = $errors->first('email');
    if (preg_match('/(?:try again in|locked for)\s+(\d+)\s+seconds/i', $firstEmailError, $matches)) {
      $lockoutSeconds = (int) $matches[1];
    }
  }

  $isIdleTimeout = ! $errors->any() && (
      session('session_expired') !== null 
      || request()->query('reason') === 'idle' 
      || request()->query('expired') === '1' 
      || request()->query('idle') === '1'
  );

  $idleMessage = session('session_expired') 
      ?? 'Your session was automatically terminated because you were idling for 3 minutes. Please sign in again.';
@endphp
<body 
  x-data="{ 
    helpdeskOpen: false,
    legalModalOpen: false,
    legalActiveTab: 'terms',
    idleTimeoutModalOpen: {{ $isIdleTimeout ? 'true' : 'false' }},
    darkMode: document.documentElement.classList.contains('dark'),
    lockoutRemaining: {{ $lockoutSeconds }},
    lockoutTotal: {{ $lockoutSeconds > 0 ? $lockoutSeconds : 60 }},
    isLocked: {{ $lockoutSeconds > 0 ? 'true' : 'false' }},
    cooldownExpired: false,
    formatCooldown(sec) {
      if (sec <= 0) return '0s';
      const m = Math.floor(sec / 60);
      const s = sec % 60;
      if (m > 0) {
        return `${m}m ${s < 10 ? '0' : ''}${s}s`;
      }
      return `${s}s`;
    },
    dismissIdleModal() {
      this.idleTimeoutModalOpen = false;
      try {
        sessionStorage.setItem('idle_modal_dismissed', '1');
      } catch (e) {}
      if (window.history && window.history.replaceState) {
        const url = new URL(window.location.href);
        if (url.searchParams.has('reason') || url.searchParams.has('expired') || url.searchParams.has('idle')) {
          url.searchParams.delete('reason');
          url.searchParams.delete('expired');
          url.searchParams.delete('idle');
          const cleanPath = url.pathname + (url.searchParams.toString() ? '?' + url.searchParams.toString() : '');
          window.history.replaceState({}, document.title, cleanPath);
        }
      }
      this.$nextTick(() => {
        const emailInput = document.getElementById('login-email');
        if (emailInput) { emailInput.focus(); }
      });
    },
    initLockout() {
      if (this.lockoutRemaining > 0) {
        const interval = setInterval(() => {
          this.lockoutRemaining--;
          if (this.lockoutRemaining <= 0) {
            clearInterval(interval);
            this.isLocked = false;
            this.cooldownExpired = true;
            this.$nextTick(() => {
              const passField = document.getElementById('login-password');
              if (passField) {
                passField.disabled = false;
                passField.value = '';
                passField.focus();
              }
            });
          }
        }, 1000);
      }
    }
  }" 
  x-init="
    initLockout();
    try {
      if (sessionStorage.getItem('idle_modal_dismissed') === '1') {
        idleTimeoutModalOpen = false;
      }
    } catch (e) {}
    if (window.history && window.history.replaceState) {
      const url = new URL(window.location.href);
      if (url.searchParams.has('reason') || url.searchParams.has('expired') || url.searchParams.has('idle')) {
        url.searchParams.delete('reason');
        url.searchParams.delete('expired');
        url.searchParams.delete('idle');
        const cleanPath = url.pathname + (url.searchParams.toString() ? '?' + url.searchParams.toString() : '');
        window.history.replaceState({}, document.title, cleanPath);
      }
    }
  "
  class="h-full bg-slate-50 dark:bg-slate-950 font-sans text-slate-800 dark:text-slate-100 antialiased selection:bg-emerald-500 selection:text-white"
>
  @if(session('session_expired') && ! $errors->any())
    <script>
      try { sessionStorage.removeItem('idle_modal_dismissed'); } catch (e) {}
    </script>
  @endif

  <div class="min-h-screen flex flex-col lg:flex-row">
    
    <!-- Left Hero & Security Authority Column (Universal Standard) -->
    <x-auth-hero />

    <!-- Right Sign In Card Column -->
    <div class="relative flex-1 flex flex-col justify-between p-6 sm:p-10 lg:p-16">
      
      <!-- Top Actions (Theme Toggle & Helpdesk trigger) -->
      <div class="flex items-center justify-end gap-3">
        <button 
          type="button" 
          @click="
            darkMode = !darkMode;
            if (darkMode) {
              document.documentElement.classList.add('dark');
              document.documentElement.style.colorScheme = 'dark';
              localStorage.setItem('fms_theme', 'dark');
            } else {
              document.documentElement.classList.remove('dark');
              document.documentElement.style.colorScheme = 'light';
              localStorage.setItem('fms_theme', 'light');
            }
          " 
          class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 hover:text-slate-900 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white transition-colors cursor-pointer"
          :title="darkMode ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
        >
          <i class="ph-bold ph-moon text-base block dark:hidden"></i>
          <i class="ph-bold ph-sun text-base hidden dark:block"></i>
        </button>

        <button 
          type="button" 
          @click="helpdeskOpen = true" 
          class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 transition-colors"
        >
          <i class="ph-bold ph-headset text-sm text-emerald-600 dark:text-emerald-400"></i>
          <span>IT Helpdesk</span>
        </button>
      </div>

      <!-- Center Auth Form Container -->
      <div class="my-auto mx-auto w-full max-w-md py-8">
        
        <!-- Hospital Header Seal & Department Badge -->
        <div class="flex items-center gap-3 pb-4 mb-6 border-b border-slate-200 dark:border-slate-800">
          <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-sm ring-1 ring-emerald-500/30">
            <i class="ph-bold ph-hospital text-lg"></i>
          </div>
          <div>
            <h2 class="text-xs font-bold text-slate-900 dark:text-white tracking-wide uppercase">Republic of the Philippines</h2>
            <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Department of Health &bull; Hospital Network</p>
          </div>
        </div>

        <div>
          <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white" id="login-title">
            Sign in to Portal
          </h2>
          <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
            Use your authorized hospital domain credentials to access the financial portal.
          </p>
        </div>

        <!-- Security Displacement Alert Banner -->
        @if(session('displacement_warning') || request()->has('displaced'))
          @php
            $dispWarning = session('displacement_warning') ?? 'Security Displacement Alert: Your account was accessed from another terminal. Only one concurrent session is authorized per hospital staff. Your previous session has been terminated.';
          @endphp
          <div class="mt-5 rounded-2xl border border-rose-200/80 bg-rose-50/80 p-4 text-xs text-rose-800 shadow-sm dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-300" role="alert">
            <div class="flex items-start gap-2.5">
              <i class="ph-fill ph-shield-warning text-lg text-rose-600 dark:text-rose-400 shrink-0 mt-0.5"></i>
              <div>
                <strong class="font-bold block">Session Displaced</strong>
                <p class="mt-0.5 leading-relaxed text-[11.5px]">{{ $dispWarning }}</p>
              </div>
            </div>
          </div>
        @endif

        <!-- Session Expired Alert -->
        @if($isIdleTimeout)
          <div class="mt-5 rounded-2xl border border-amber-200/80 bg-amber-50/80 p-4 text-xs text-amber-800 shadow-sm dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-300" role="alert">
            <div class="flex items-center justify-between gap-2.5">
              <div class="flex items-center gap-2.5">
                <i class="ph-bold ph-clock-countdown text-lg text-amber-600 dark:text-amber-400 shrink-0"></i>
                <span>{{ $idleMessage }}</span>
              </div>
              <button 
                type="button" 
                @click="idleTimeoutModalOpen = true" 
                class="shrink-0 text-[11px] font-bold text-amber-700 dark:text-amber-300 underline hover:text-amber-900 dark:hover:text-white cursor-pointer"
              >
                View Notice
              </button>
            </div>
          </div>
        @endif

        <!-- Rate Limit & Cooldown Protection Alert (Dynamic Live Countdown) -->
        <template x-if="lockoutTotal > 0 && (isLocked || cooldownExpired)">
          <div 
            class="mt-5 rounded-2xl border p-4 shadow-sm transition-all duration-300"
            :class="isLocked 
              ? 'border-rose-200/90 bg-gradient-to-br from-rose-50/90 via-amber-50/60 to-rose-50/90 text-rose-950 dark:border-rose-900/60 dark:from-rose-950/40 dark:via-amber-950/20 dark:to-rose-950/40 dark:text-rose-200' 
              : 'border-emerald-200/90 bg-emerald-50/80 text-emerald-900 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-200'"
            role="alert"
          >
            <!-- Active Lockout State -->
            <div x-show="isLocked" class="space-y-3">
              <div class="flex items-start gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-rose-500/15 text-rose-600 dark:bg-rose-400/10 dark:text-rose-400 ring-1 ring-rose-500/25">
                  <i class="ph-fill ph-shield-warning text-lg animate-pulse"></i>
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center justify-between gap-2">
                    <strong class="font-bold text-xs uppercase tracking-wider text-rose-800 dark:text-rose-300">
                      Security Cooldown Active
                    </strong>
                    <!-- Live Countdown Badge -->
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-rose-200/80 text-rose-900 dark:bg-rose-900/60 dark:text-rose-200 border border-rose-300/60 dark:border-rose-700/60 shadow-xs">
                      <i class="ph-bold ph-timer text-[13px]"></i>
                      <span x-text="formatCooldown(lockoutRemaining)"></span>
                    </span>
                  </div>
                  <p class="mt-1 text-[11.5px] leading-relaxed text-rose-800/90 dark:text-rose-300/85">
                    Too many unsuccessful authentication attempts. Terminal access is temporarily paused to protect hospital financial records.
                  </p>
                </div>
              </div>

              <!-- Animated Progress Bar -->
              <div class="w-full bg-rose-200/60 dark:bg-rose-950/80 rounded-full h-1.5 overflow-hidden">
                <div 
                  class="bg-rose-500 dark:bg-rose-400 h-1.5 rounded-full transition-all duration-1000 ease-linear"
                  :style="`width: ${Math.max(0, Math.min(100, (lockoutRemaining / lockoutTotal) * 100))}%`"
                ></div>
              </div>

              <div class="flex items-center justify-between pt-1 text-[11px] text-rose-800/90 dark:text-rose-300/90">
                <span>Hospital security protocol enforced.</span>
                <button 
                  type="button" 
                  @click="helpdeskOpen = true" 
                  class="font-semibold underline hover:text-rose-950 dark:hover:text-white cursor-pointer"
                >
                  Contact IT Helpdesk
                </button>
              </div>
            </div>

            <!-- Lockout Expired State -->
            <div x-show="!isLocked && cooldownExpired" x-cloak class="flex items-start gap-3">
              <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-500/15 text-emerald-600 dark:bg-emerald-400/10 dark:text-emerald-400 ring-1 ring-emerald-500/20">
                <i class="ph-fill ph-check-circle text-lg"></i>
              </div>
              <div class="flex-1">
                <strong class="font-bold text-xs uppercase tracking-wider text-emerald-800 dark:text-emerald-300 block">
                  Cooldown Period Elapsed
                </strong>
                <p class="mt-0.5 text-[11.5px] leading-relaxed text-emerald-700 dark:text-emerald-300/90">
                  You may now attempt to sign in again. Please verify your hospital credentials.
                </p>
              </div>
            </div>
          </div>
        </template>

        <!-- General Validation Errors (Shown when NOT in lockout mode) -->
        @if($errors->any() && !$lockoutSeconds && !session('displacement_warning'))
          <div class="mt-5 rounded-2xl border border-rose-200/80 bg-rose-50/80 p-4 text-xs text-rose-800 shadow-sm dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-300" role="alert">
            <div class="flex items-center gap-2.5">
              <i class="ph-bold ph-warning-circle text-lg text-rose-600 dark:text-rose-400 shrink-0"></i>
              <span>{{ $errors->first() }}</span>
            </div>
          </div>
        @endif

        <!-- Login Form -->
        <form id="login-form" method="POST" action="{{ route('login.post') }}" novalidate class="mt-6 space-y-4" @submit="try { sessionStorage.setItem('idle_modal_dismissed', '1'); } catch (e) {}">
          @csrf
          <input type="hidden" name="device_uuid" id="login-device-uuid">

          <!-- Email Field -->
          <div>
            <label for="login-email" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
              Email address
            </label>
            <div class="relative">
              <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 z-10">
                <i class="ph-bold ph-envelope-simple text-base"></i>
              </div>
              <input 
                id="login-email" 
                name="email" 
                type="email" 
                autocomplete="email" 
                autocorrect="off" 
                autocapitalize="off" 
                spellcheck="false"
                placeholder="name@hospital.gov.ph" 
                value="{{ old('email') }}" 
                maxlength="255" 
                required 
                autofocus
                class="autofill-fix w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 py-2.5 pl-10 pr-3 text-sm text-slate-900 dark:text-white placeholder:text-slate-400 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/25 shadow-sm transition-all"
              >
            </div>
          </div>

          <!-- Password Field -->
          <div>
            <label for="login-password" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
              Password
            </label>
            <div class="relative">
              <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 z-10">
                <i class="ph-bold ph-lock-key text-base"></i>
              </div>
              <input 
                id="login-password" 
                name="password" 
                type="password" 
                autocomplete="current-password" 
                placeholder="Enter authorized password" 
                maxlength="128" 
                required
                :disabled="isLocked"
                class="autofill-fix w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 py-2.5 pl-10 pr-11 text-sm text-slate-900 dark:text-white placeholder:text-slate-400 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/25 shadow-sm transition-all disabled:opacity-60 disabled:cursor-not-allowed disabled:bg-slate-100 dark:disabled:bg-slate-800"
              >
              <button 
                type="button" 
                data-password-toggle 
                aria-label="Show password" 
                aria-pressed="false"
                class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors z-10 cursor-pointer"
              >
                <i class="ph-bold ph-eye text-base"></i>
              </button>
            </div>

            <!-- Sub-Row: Caps Lock Warning & Forgot Password Trigger -->
            <div class="mt-2 flex items-center justify-between min-h-[22px]">
              <div id="caps-lock-warning" style="display: none;" class="items-center gap-1.5 text-[11px] font-semibold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 px-2 py-0.5 rounded-lg ring-1 ring-amber-500/20" role="alert" aria-live="polite">
                <i class="ph-fill ph-warning"></i>
                <span>Caps Lock is ON</span>
              </div>
              <div class="ml-auto">
                <button 
                  type="button" 
                  @click="helpdeskOpen = true" 
                  class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300 transition-colors cursor-pointer"
                >
                  Forgot password?
                </button>
              </div>
            </div>
          </div>

          <!-- Submit Button -->
          <div class="pt-2">
            <button 
              type="submit" 
              :disabled="isLocked"
              :class="isLocked 
                ? 'bg-slate-300 dark:bg-slate-800 text-slate-500 dark:text-slate-400 cursor-not-allowed border border-slate-300/80 dark:border-slate-700 shadow-none' 
                : 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm ring-1 ring-emerald-600/20 focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 dark:focus:ring-offset-slate-900 cursor-pointer'"
              class="login-submit w-full inline-flex items-center justify-center gap-2 rounded-xl py-2.5 px-4 text-sm font-semibold transition-all focus:outline-none"
            >
              <span x-show="!isLocked" class="inline-flex items-center gap-2">
                <i class="ph-bold ph-sign-in text-base"></i>
                <span>Sign in</span>
              </span>

              <span x-show="isLocked" x-cloak class="inline-flex items-center gap-2">
                <i class="ph-bold ph-lock-key text-base animate-pulse"></i>
                <span>Temporarily Locked (<span x-text="formatCooldown(lockoutRemaining)"></span>)</span>
              </span>
            </button>
          </div>
        </form>

      </div>

      <!-- Bottom Mini Legal & Governance Footer -->
      <div class="text-center text-xs text-slate-400 dark:text-slate-500 space-y-2 py-2">
        <div class="flex flex-wrap items-center justify-center gap-x-3 gap-y-1 text-[11.5px] text-slate-500 dark:text-slate-400 font-medium">
          <button 
            type="button" 
            @click="legalActiveTab = 'terms'; legalModalOpen = true" 
            class="hover:text-emerald-600 dark:hover:text-emerald-400 hover:underline transition-colors cursor-pointer"
          >
            Terms &amp; Conditions
          </button>
          <span class="text-slate-300 dark:text-slate-700">&bull;</span>
          <button 
            type="button" 
            @click="legalActiveTab = 'privacy'; legalModalOpen = true" 
            class="hover:text-emerald-600 dark:hover:text-emerald-400 hover:underline transition-colors cursor-pointer"
          >
            Privacy Policy (RA 10173)
          </button>
          <span class="text-slate-300 dark:text-slate-700">&bull;</span>
          <button 
            type="button" 
            @click="legalActiveTab = 'aup'; legalModalOpen = true" 
            class="hover:text-emerald-600 dark:hover:text-emerald-400 hover:underline transition-colors cursor-pointer"
          >
            Acceptable Use Policy
          </button>
        </div>

        <div class="text-[11px] text-slate-400 dark:text-slate-500">
          Hospital Financial Management System &bull; Secure Terminal Gateway &bull; DOH Network
        </div>
      </div>
    </div>

  </div>

  <!-- Hospital Legal & Regulatory Compliance Modal (Alpine.js) -->
  <x-auth-legal-modal />

  <!-- Hospital MIS Support Directory Modal (Alpine.js) -->
  <x-auth-helpdesk-modal />

  <!-- Hospital Security Idle Timeout Modal (Alpine.js) -->
  <x-auth-idle-modal />

  <!-- Workstation Device Binding & Input Scripts -->
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const toggle = document.querySelector('[data-password-toggle]');
      const password = document.getElementById('login-password');
      const capsWarning = document.getElementById('caps-lock-warning');
      let maskTimer = null;

      // 1. Password Visibility Toggle with 5-Second Auto-Masking (Anti-Shoulder Surfing)
      if (toggle && password) {
        const revertToPassword = () => {
          password.type = 'password';
          toggle.setAttribute('aria-pressed', 'false');
          toggle.setAttribute('aria-label', 'Show password');
          const icon = toggle.querySelector('i');
          if (icon) {
            icon.className = 'ph-bold ph-eye text-base';
          }
          if (maskTimer) {
            clearTimeout(maskTimer);
            maskTimer = null;
          }
        };

        toggle.addEventListener('click', () => {
          const isCurrentlyPassword = password.type === 'password';
          if (isCurrentlyPassword) {
            password.type = 'text';
            toggle.setAttribute('aria-pressed', 'true');
            toggle.setAttribute('aria-label', 'Hide password');
            const icon = toggle.querySelector('i');
            if (icon) {
              icon.className = 'ph-bold ph-eye-slash text-base';
            }

            // Auto-revert back to masked dots after 5 seconds
            if (maskTimer) clearTimeout(maskTimer);
            maskTimer = setTimeout(revertToPassword, 5000);
          } else {
            revertToPassword();
          }
        });

        // Revert instantly on blur
        password.addEventListener('blur', () => {
          if (password.type === 'text') {
            revertToPassword();
          }
          if (capsWarning) {
            capsWarning.style.display = 'none';
          }
        });

        // 2. Caps Lock Detection Warning
        const checkCapsLock = (e) => {
          if (e.getModifierState && e.getModifierState('CapsLock')) {
            capsWarning.style.display = 'inline-flex';
          } else {
            capsWarning.style.display = 'none';
          }
        };

        password.addEventListener('keydown', checkCapsLock);
        password.addEventListener('keyup', checkCapsLock);
      }

      // 3. Prevent double-submission and provide feedback
      const form = document.getElementById('login-form');
      const submitBtn = document.querySelector('.login-submit');
      if (form && submitBtn) {
        form.addEventListener('submit', (e) => {
          if (submitBtn.disabled || submitBtn.hasAttribute('disabled')) {
            e.preventDefault();
            return false;
          }
          if (form.checkValidity()) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="inline-block animate-spin mr-2"><i class="ph-bold ph-spinner"></i></span>Authenticating...';
          }
        });
      }

      // 4. Persistent Hardware Workstation UUID Binding
      try {
        let deviceUuid = localStorage.getItem('fms_device_uuid');
        if (!deviceUuid) {
          deviceUuid = 'ws-' + ([1e7]+-1e3+-4e3+-8e3+-1e11).replace(/[018]/g, c =>
            (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16)
          );
          localStorage.setItem('fms_device_uuid', deviceUuid);
        }
        const uuidInput = document.getElementById('login-device-uuid');
        if (uuidInput) uuidInput.value = deviceUuid;
        document.cookie = 'fms_workstation_token=' + deviceUuid + '; path=/; max-age=157680000; SameSite=Lax';
      } catch (_) {}

      // 5. Prevent browser bfcache restoration if navigating back while authenticated
      window.addEventListener('pageshow', (event) => {
        if (event.persisted || (window.performance && window.performance.getEntriesByType('navigation')[0]?.type === 'back_forward')) {
          window.location.reload();
        }
      });
    });
  </script>
</body>
</html>

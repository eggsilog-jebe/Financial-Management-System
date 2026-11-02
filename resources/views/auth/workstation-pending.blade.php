<!doctype html>
<html lang="en" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Workstation Authorization Pending &mdash; HIMS &bull; FMS</title>
  <meta name="description" content="This computer is not yet authorized. An authorization request has been dispatched in real-time to the Super Administrator.">
  <x-favicon />

  <!-- Google Fonts: Inter & JetBrains Mono -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

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
    /* Radar Pulsing Rings Animation */
    .radar-container {
      position: relative;
      width: 72px;
      height: 72px;
      margin: 0 auto 16px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .radar-ring {
      position: absolute;
      inset: 0;
      border-radius: 50%;
      border: 2px solid rgba(245, 158, 11, 0.4);
      animation: radar-pulse 2.2s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
    }
    .radar-ring-2 {
      animation-delay: 0.75s;
    }
    @keyframes radar-pulse {
      0%   { transform: scale(0.65); opacity: 0.9; }
      100% { transform: scale(1.6);  opacity: 0;   }
    }

    /* ─── Elevated Card ───────────────────────────────────── */
    .login-card {
      width: 100%;
      max-width: 440px;
      padding: 32px 30px;
      border-radius: 16px;
      border: 1px solid #eef2f6;
      background: #ffffff;
      box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.07), 0 4px 6px -2px rgba(15, 23, 42, 0.03);
    }
    html.dark .login-card {
      background: #0f172a;
      border-color: #1e293b;
      box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.5);
    }
  </style>
</head>
<body 
  x-data="{ 
    helpdeskOpen: false,
    darkMode: document.documentElement.classList.contains('dark')
  }" 
  class="h-full bg-slate-50 dark:bg-slate-950 font-sans text-slate-800 dark:text-slate-100 antialiased selection:bg-emerald-500 selection:text-white"
>

  <div class="min-h-screen flex flex-col lg:flex-row">
    
    <!-- Left Hero & Security Authority Column (Universal Standard) -->
    <x-auth-hero />

    <!-- Right Holding Panel Column -->
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
          class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 transition-colors cursor-pointer"
        >
          <i class="ph-bold ph-headset text-sm text-emerald-600 dark:text-emerald-400"></i>
          <span>IT Helpdesk</span>
        </button>
      </div>

      <!-- Center Auth Form Container -->
      <div class="my-auto mx-auto w-full flex flex-col items-center py-6">
        
        <div class="login-card w-full max-w-md">
          <!-- Hospital Header Seal & Department Badge -->
          <div class="flex items-center gap-3 pb-4 mb-5 border-b border-slate-200 dark:border-slate-800">
          <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-sm ring-1 ring-emerald-500/30">
            <i class="ph-bold ph-hospital text-lg"></i>
          </div>
          <div>
            <h2 class="text-xs font-bold text-slate-900 dark:text-white tracking-wide uppercase">Republic of the Philippines</h2>
            <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Department of Health &bull; Hospital Network</p>
          </div>
        </div>

        <!-- Radar Animation & Header -->
        <div class="text-center mb-4">
          <div class="radar-container" aria-hidden="true">
            <div class="radar-ring"></div>
            <div class="radar-ring radar-ring-2"></div>
            <div class="relative z-10 flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-500 text-white shadow-md shadow-amber-500/20 text-xl">
              <i class="ph-bold {{ $deviceIcon ?? 'ph-desktop' }}"></i>
            </div>
          </div>

          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 ring-1 ring-amber-600/20 dark:bg-amber-950/60 dark:text-amber-300 font-mono tracking-wider uppercase mb-2">
            <span class="h-2 w-2 rounded-full bg-amber-500 animate-ping"></span>
            Awaiting Super Admin Approval
          </span>

          <h2 class="text-xl font-bold tracking-tight text-slate-900 dark:text-white mt-2 mb-1" id="workstation-title">
            Unrecognized Workstation Detected
          </h2>
          <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
            This computer is not yet authorized for user <strong>{{ $user->name }}</strong>.
            An authorization request has been dispatched in real-time to the Super Administrator.
          </p>
        </div>

        <div id="rejection-alert" class="hidden mb-4 rounded-xl border border-rose-200/80 bg-rose-50/80 p-3 text-xs text-rose-800 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-300 items-center gap-2" role="alert">
          <i class="ph-bold ph-x-circle text-base text-rose-600 dark:text-rose-400 shrink-0"></i>
          <span id="rejection-text">Access was rejected by the administrator.</span>
        </div>

        <!-- Device Specification Card -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm space-y-2.5 text-xs">
          <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800/80">
            <span class="text-slate-400 flex items-center gap-1.5">
              <i class="ph-bold ph-user"></i> Personnel
            </span>
            <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $user->name }} ({{ $user->role }})</span>
          </div>
          <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800/80">
            <span class="text-slate-400 flex items-center gap-1.5">
              <i class="ph-bold {{ $deviceIcon ?? 'ph-desktop' }}"></i> Device Type
            </span>
            <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-950/60 dark:text-emerald-300">
              <i class="ph-bold {{ $deviceIcon ?? 'ph-desktop' }} text-xs"></i>
              {{ $deviceType ?? 'Desktop' }}
            </span>
          </div>
          <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800/80">
            <span class="text-slate-400 flex items-center gap-1.5">
              <i class="ph-bold ph-cpu"></i> Operating System
            </span>
            <span class="font-medium text-slate-700 dark:text-slate-300">{{ $platform }}</span>
          </div>
          <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800/80">
            <span class="text-slate-400 flex items-center gap-1.5">
              <i class="ph-bold ph-globe"></i> Browser
            </span>
            <span class="font-medium text-slate-700 dark:text-slate-300">{{ $browser }}</span>
          </div>
          <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800/80">
            <span class="text-slate-400 flex items-center gap-1.5">
              <i class="ph-bold ph-map-pin"></i> IP Address
            </span>
            <span class="font-mono text-slate-700 dark:text-slate-300">{{ $ip }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-slate-400 flex items-center gap-1.5">
              <i class="ph-bold ph-fingerprint"></i> Device Fingerprint
            </span>
            <span class="font-mono text-[11px] text-slate-400 dark:text-slate-500">{{ substr($deviceUuid, 0, 16) }}...</span>
          </div>
        </div>

        <!-- Live Polling Indicator -->
        <div class="mt-4 rounded-xl border border-emerald-200/80 bg-emerald-50/70 p-3 text-center dark:border-emerald-900/50 dark:bg-emerald-950/30">
          <div class="flex items-center justify-center gap-2 text-xs font-medium text-emerald-800 dark:text-emerald-300">
            <i class="ph-bold ph-broadcast animate-pulse text-emerald-600 dark:text-emerald-400"></i>
            <span id="polling-text">Monitoring Super Admin decision in real-time&hellip;</span>
          </div>
        </div>

          <!-- Cancel / Sign out -->
          <div class="mt-4 text-center">
            <form method="POST" action="{{ route('workstation.cancel') }}" class="inline">
              @csrf
              <button type="submit" class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500 hover:text-emerald-600 dark:text-slate-400 dark:hover:text-emerald-400 transition-colors cursor-pointer">
                <i class="ph-bold ph-arrow-left"></i>
                <span>Cancel request and sign out</span>
              </button>
            </form>
          </div>

        </div>

      </div>

      <!-- Bottom Mini Footer -->
      <div class="text-center text-xs text-slate-400 dark:text-slate-500">
        Hospital Financial Management System &bull; Secure Terminal Gateway
      </div>
    </div>

  </div>

  <!-- IT Helpdesk Support Directory Modal -->
  <x-auth-helpdesk-modal />

  <script>
    const pollUrl = '{{ route('workstation.status') }}';
    const pollText = document.getElementById('polling-text');
    let pollInterval = null;
    let pollFailCount = 0;

    async function checkApprovalStatus() {
      try {
        const response = await fetch(pollUrl, {
          method: 'GET',
          credentials: 'include',
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
          }
        });

        if (!response.ok) {
          pollFailCount++;
          if (pollFailCount >= 5) {
            // After 5 consecutive failures, try a full page reload to re-establish session
            window.location.reload();
          }
          return;
        }

        pollFailCount = 0; // reset on success
        const data = await response.json();

        if (data.status === 'approved') {
          clearInterval(pollInterval);
          pollText.textContent = '✅ Workstation authorized! Redirecting...';
          pollText.parentElement.classList.remove('bg-emerald-50/70', 'border-emerald-200/80', 'dark:border-emerald-900/50', 'dark:bg-emerald-950/30');
          pollText.parentElement.classList.add('bg-emerald-100', 'border-emerald-300');
          // Use replace to prevent going back to pending page
          setTimeout(() => {
            window.location.replace(data.redirect_url || '{{ route('two-factor.challenge') }}');
          }, 600);
        } else if (data.status === 'rejected') {
          clearInterval(pollInterval);
          const rejAlert = document.getElementById('rejection-alert');
          rejAlert.classList.remove('hidden');
          rejAlert.classList.add('flex');
          if (data.message) {
            document.getElementById('rejection-text').textContent = data.message;
          }
          setTimeout(() => {
            window.location.href = data.redirect_url || '{{ route('login') }}';
          }, 3500);
        } else if (data.status === 'revoked') {
          clearInterval(pollInterval);
          window.location.href = '{{ route('login') }}';
        } else if (data.status === 'unauthenticated') {
          clearInterval(pollInterval);
          window.location.href = '{{ route('login') }}';
        }
        // status === 'pending' or 'unknown': keep polling
      } catch (err) {
        // network error — keep retrying silently
      }
    }

    // Poll every 2.5 seconds
    pollInterval = setInterval(checkApprovalStatus, 2500);
    // Also fire immediately on load
    checkApprovalStatus();
  </script>
</body>
</html>

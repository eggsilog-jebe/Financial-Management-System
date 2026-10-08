<!doctype html>
<html lang="en" class="h-full">
  <head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <title>@yield('title', 'Financial Management System (FMS)')</title>
    <x-favicon />

    <!-- Google Fonts: Plus Jakarta Sans, Inter & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Immediate Dark Mode Boot to Prevent FOUC (Supports light, dark, system) -->
    <script>
      (function() {
        const dbTheme = @json(auth()->user()?->theme_preference ?? 'system');
        let savedTheme = localStorage.getItem('fms_theme');
        if (!savedTheme && dbTheme) {
          savedTheme = dbTheme;
          localStorage.setItem('fms_theme', dbTheme);
          localStorage.setItem('himsMainTheme', dbTheme);
        }
        if (!savedTheme) savedTheme = 'system';

        const systemPrefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        const shouldBeDark = savedTheme === 'dark' || (savedTheme === 'system' && systemPrefersDark);

        if (shouldBeDark) {
          document.documentElement.classList.add('dark');
        } else {
          document.documentElement.classList.remove('dark');
        }

        // Smooth theme applier orchestration
        function applyThemeMorph(isDark, themeName) {
          const updateDom = function() {
            if (isDark) {
              document.documentElement.classList.add('dark');
            } else {
              document.documentElement.classList.remove('dark');
            }
            window.dispatchEvent(new CustomEvent('theme-applied', { detail: { theme: themeName, isDark: isDark } }));
          };

          const prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
          if (!prefersReducedMotion && typeof document.startViewTransition === 'function') {
            try {
              document.startViewTransition(updateDom);
              return;
            } catch (e) {}
          }

          document.documentElement.classList.add('theme-transitioning');
          updateDom();
          setTimeout(function() {
            document.documentElement.classList.remove('theme-transitioning');
          }, 340);
        }

        // Live system OS listener
        if (window.matchMedia) {
          window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
            const currentMode = localStorage.getItem('fms_theme') || 'system';
            if (currentMode === 'system') {
              applyThemeMorph(e.matches, 'system');
            }
          });
        }

        window.setFmsTheme = function(theme) {
          localStorage.setItem('fms_theme', theme);
          localStorage.setItem('himsMainTheme', theme);
          const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
          const isDark = theme === 'dark' || (theme === 'system' && prefersDark);
          applyThemeMorph(isDark, theme);
        };

        // Instant sidebar collapsed state check to prevent layout shift
        try {
          if (localStorage.getItem('fms_sidebar_collapsed') === 'true') {
            document.documentElement.classList.add('sidebar-collapsed');
          }
        } catch (e) {}
      })();
    </script>

    <!-- Preload Transition Guard & Instant Collapsed Layout Stability -->
    <style id="fms-preload-guard">
      /* Disable transition flicker during initial page paint */
      html.fms-preload *,
      html.fms-preload *::before,
      html.fms-preload *::after {
        transition: none !important;
        animation-duration: 0.001ms !important;
      }
      /* Instant sidebar collapsed layout before Alpine boots */
      html.sidebar-collapsed aside#fms-sidebar {
        width: 5rem !important;
      }
      html.sidebar-collapsed aside#fms-sidebar .sidebar-label,
      html.sidebar-collapsed aside#fms-sidebar .sidebar-indicator,
      html.sidebar-collapsed aside#fms-sidebar .sidebar-brand-text,
      html.sidebar-collapsed aside#fms-sidebar .sidebar-submenu,
      html.sidebar-collapsed aside#fms-sidebar .sidebar-caret {
        display: none !important;
      }
      html.sidebar-collapsed aside#fms-sidebar .sidebar-link {
        justify-content: center !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
      }
    </style>
    <script>
      document.documentElement.classList.add('fms-preload');
      window.addEventListener('DOMContentLoaded', function() {
        requestAnimationFrame(function() {
          document.documentElement.classList.remove('fms-preload');
        });
      });
    </script>

    <!-- Tailwind CSS v4 & Localized Alpine.js / Phosphor Icons Bundle -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
  </head>
  <body 
    x-data="{
      sidebarOpen: false,
      sidebarCollapsed: localStorage.getItem('fms_sidebar_collapsed') === 'true',
      toggleSidebarCollapse() {
        this.sidebarCollapsed = !this.sidebarCollapsed;
        try {
          localStorage.setItem('fms_sidebar_collapsed', this.sidebarCollapsed);
          if (this.sidebarCollapsed) {
            document.documentElement.classList.add('sidebar-collapsed');
          } else {
            document.documentElement.classList.remove('sidebar-collapsed');
          }
        } catch (e) {}
      },
      init() {
        window.addEventListener('keydown', (e) => {
          if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'b') {
            if (!['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) {
              e.preventDefault();
              this.toggleSidebarCollapse();
            }
          }
        });
        this.$watch('idleModalOpen', () => this.syncModalLock());
        this.$watch('systemModal.open', () => this.syncModalLock());
      },
      syncModalLock() {
        if (this.idleModalOpen || (this.systemModal && this.systemModal.open)) {
          document.body.classList.add('overflow-hidden', 'modal-open');
        } else {
          document.body.classList.remove('overflow-hidden', 'modal-open');
        }
      },
      darkMode: document.documentElement.classList.contains('dark'),
      systemModal: {
        open: false,
        title: 'System Notification',
        message: '',
        icon: 'ph-info'
      },
      idleModalOpen: false
    }"
    @keydown.escape.window="systemModal.open = false"
    @open-idle-modal.window="idleModalOpen = true"
    @close-idle-modal.window="idleModalOpen = false"
    @theme-applied.window="darkMode = $event.detail.isDark"
    class="h-full bg-slate-50 dark:bg-slate-950 font-sans text-slate-800 dark:text-slate-100 antialiased selection:bg-emerald-500 selection:text-white"
    data-module="@yield('module', 'main')" 
    data-page="@yield('page', 'dashboard')"
  >
    {{-- ── Global Navigation Loading Modal (Backdrop Blur + Executive Healthcare Card) ────── --}}
    <div 
      id="fms-navigation-loader" 
      class="fixed inset-0 z-[9999] flex items-center justify-center p-4 transition-all duration-200 opacity-0 pointer-events-none invisible"
      role="status" 
      aria-live="polite" 
      aria-label="Loading page content"
    >
      <!-- Heavy Backdrop Blur Layer -->
      <div 
        id="fms-loader-backdrop"
        class="fixed inset-0 bg-slate-950/40 dark:bg-slate-950/70 backdrop-blur-md transition-opacity duration-200 opacity-0 pointer-events-auto"
      ></div>

      <!-- Centered Pop-up Modal Card -->
      <div 
        id="fms-loader-card"
        class="relative w-full max-w-sm transform scale-95 transition-all duration-200 opacity-0 overflow-hidden rounded-3xl bg-white/95 p-7 shadow-2xl ring-1 ring-slate-200/80 backdrop-blur-xl dark:bg-slate-900/95 dark:ring-slate-800 text-center pointer-events-auto select-none"
      >
        <!-- Luminous Emerald Ambient Radial Gradients -->
        <div class="pointer-events-none absolute -top-12 -left-12 h-32 w-32 rounded-full bg-emerald-500/10 blur-2xl"></div>
        <div class="pointer-events-none absolute -bottom-12 -right-12 h-32 w-32 rounded-full bg-teal-500/10 blur-2xl"></div>

        <!-- Animated Radar Spinner & Hospital Cross Seal -->
        <div class="relative mx-auto flex h-16 w-16 items-center justify-center">
          <span class="absolute inset-0 rounded-2xl bg-emerald-500/20 animate-ping opacity-60"></span>
          <div class="absolute inset-0 rounded-2xl border-[3px] border-emerald-500/20 border-t-emerald-600 dark:border-t-emerald-400 animate-spin"></div>
          <div class="relative flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 text-white shadow-md ring-1 ring-emerald-500/30">
            <i class="ph-bold ph-hospital text-xl"></i>
          </div>
        </div>

        <!-- Title & Subtitle Context -->
        <div class="mt-4">
          <h3 id="fms-loader-title" class="font-bold text-base text-slate-900 dark:text-white tracking-tight truncate px-2">
            Loading Module...
          </h3>
          <p id="fms-loader-desc" class="mt-1 text-xs text-slate-500 dark:text-slate-400 font-medium truncate px-2">
            Preparing hospital ledger & clinical records
          </p>
        </div>

        <!-- Indeterminate Progress Track -->
        <div class="mt-5 w-full bg-slate-100 dark:bg-slate-800 rounded-full h-1.5 overflow-hidden">
          <div class="fms-loader-indeterminate-bar h-full bg-gradient-to-r from-emerald-500 via-teal-400 to-emerald-600 rounded-full"></div>
        </div>

        <p class="mt-3.5 text-[10.5px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">
          Hospital Financial Management System
        </p>
      </div>
    </div>

    <div class="min-h-screen flex bg-slate-50 dark:bg-slate-950 transition-colors">
      <!-- Master Navigation Sidebar -->
      @include('partials.sidebar')

      <!-- Primary Content Column -->
      <div class="flex-1 flex flex-col min-w-0 overflow-x-hidden">
        @include('partials.headbar')

        <main class="flex-1 w-full px-6 lg:px-8 py-6 animate-fade-in" id="main-content">
          @yield('content')
        </main>

        @include('partials.footer')
      </div>
    </div>

    <!-- Toast Notification Container -->
    <div id="toast-container" class="fixed bottom-5 right-5 z-50 flex flex-col gap-2 max-w-sm pointer-events-none" role="status" aria-live="polite"></div>

    {{-- ── Idle Session Timeout Warning Modal (Alpine.js + Backdrop Blur) ────────────── --}}
    <div 
      id="idleTimeoutModal"
      x-show="idleModalOpen" 
      x-cloak
      class="fixed inset-0 z-50 overflow-y-auto" 
      aria-labelledby="idleTimeoutModalLabel" 
      role="dialog" 
      aria-modal="true"
    >
      <div 
        x-show="idleModalOpen"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-slate-950/60 backdrop-blur-md transition-opacity"
      ></div>

      <div class="fixed inset-0 z-10 flex min-h-full items-center justify-center p-4 pointer-events-none">
        <div 
          x-show="idleModalOpen"
          x-transition:enter="ease-out duration-300"
          x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
          x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
          x-transition:leave="ease-in duration-200"
          x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
          x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
          class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800 pointer-events-auto"
        >
          <div class="flex items-center gap-3">
            <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 ring-1 ring-amber-500/20 dark:bg-amber-950/50 dark:text-amber-400">
              <i class="ph-bold ph-clock-countdown text-2xl"></i>
            </span>
            <div>
              <h3 class="text-base font-bold text-slate-900 dark:text-white" id="idleTimeoutModalLabel">Session Expiring Soon</h3>
              <p class="text-xs text-amber-600 dark:text-amber-400 font-medium">Hospital Security &bull; Inactivity Detected</p>
            </div>
          </div>

          <div class="mt-4">
            <p class="text-xs text-slate-600 dark:text-slate-300">
              Your HIMS session will automatically sign out to protect patient health records and financial data per RA 10173:
            </p>

            <div class="my-5 flex justify-center">
              <div class="flex flex-col items-center justify-center rounded-2xl bg-amber-50 px-8 py-4 ring-1 ring-amber-200 dark:bg-amber-950/40 dark:ring-amber-800/40">
                <span id="idle-countdown-seconds" class="font-mono text-4xl font-bold tabular-nums text-amber-700 dark:text-amber-400">60</span>
                <span class="mt-1 text-[10px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-500">SECONDS REMAINING</span>
              </div>
            </div>

            <p class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
              <i class="ph-bold ph-shield-warning text-amber-500 text-sm"></i>
              <span>Active unsaved drafts or terminal drawers remain secure.</span>
            </p>
          </div>

          <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
            <button 
              type="button" 
              id="idle-logout-now"
              class="rounded-xl px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800 transition-colors"
            >
              Sign Out Now
            </button>
            <button 
              type="button" 
              id="idle-stay-logged-in"
              class="inline-flex items-center gap-1.5 rounded-xl bg-amber-600 px-5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-amber-700 ring-1 ring-amber-600/20 transition-all"
            >
              <i class="ph-bold ph-hand-waving"></i>
              <span>I'm Still Here</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Hidden form for automated idle logout POST -->
    <form id="idle-logout-form" method="POST" action="{{ route('logout') }}" class="hidden">
      @csrf
      <input type="hidden" name="reason" value="idle">
    </form>

    {{-- ── Global Executive Alert Modal (Alpine.js) ─────────────────────────────── --}}
    <div 
      x-show="systemModal.open" 
      x-cloak 
      class="fixed inset-0 z-50 overflow-y-auto" 
      role="dialog" 
      aria-modal="true"
    >
      <div 
        x-show="systemModal.open"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-slate-950/60 backdrop-blur-md transition-opacity"
        @click="systemModal.open = false"
      ></div>

      <div class="fixed inset-0 z-10 flex min-h-full items-center justify-center p-4 pointer-events-none">
        <div 
          x-show="systemModal.open"
          x-transition:enter="ease-out duration-300"
          x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
          x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
          x-transition:leave="ease-in duration-200"
          x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
          x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
          @click.outside="systemModal.open = false"
          class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800 text-left pointer-events-auto"
        >
          <div class="flex items-center gap-3">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-500/20 dark:bg-emerald-950/40 dark:text-emerald-400">
              <i class="ph-bold" :class="systemModal.icon"></i>
            </span>
            <h3 class="text-base font-bold text-slate-900 dark:text-white" x-text="systemModal.title"></h3>
          </div>
          <div class="mt-3">
            <p class="text-xs text-slate-600 dark:text-slate-300" x-text="systemModal.message"></p>
          </div>
          <div class="mt-5 flex justify-end pt-3 border-t border-slate-100 dark:border-slate-800">
            <button 
              @click="systemModal.open = false" 
              type="button" 
              class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700"
            >
              OK
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Compatibility Shim for Bootstrap Modal calls to ensure zero legacy breakage -->
    <script>
      window.bootstrap = {
        Modal: {
          getOrCreateInstance: function(el) {
            return {
              show: function() {
                if (el.id === 'idleTimeoutModal') {
                  const root = document.querySelector('[x-data]');
                  if (root && root._x_dataStack) root._x_dataStack[0].idleModalOpen = true;
                } else {
                  window.dispatchEvent(new CustomEvent('open-modal', { detail: el.id }));
                }
              },
              hide: function() {
                if (el.id === 'idleTimeoutModal') {
                  const root = document.querySelector('[x-data]');
                  if (root && root._x_dataStack) root._x_dataStack[0].idleModalOpen = false;
                } else {
                  window.dispatchEvent(new CustomEvent('close-modal', { detail: el.id }));
                }
              }
            };
          },
          getInstance: function(el) {
            return window.bootstrap.Modal.getOrCreateInstance(el);
          }
        }
      };

      // Global system notification replacement for alert()
      window.showSystemModal = function(message, title = 'System Notification', icon = 'ph-info') {
        const root = document.querySelector('[x-data]');
        if (root && root._x_dataStack) {
          const data = root._x_dataStack[0];
          data.systemModal.message = message;
          data.systemModal.title = title;
          data.systemModal.icon = icon.startsWith('ph-') ? icon : 'ph-' + icon;
          data.systemModal.open = true;
        } else {
          window.alert(message);
        }
      };

      window.alert = function(message) {
        let title = 'System Notification';
        let icon = 'ph-info';
        if (typeof message === 'string') {
          const lower = message.toLowerCase();
          if (lower.includes('export') || lower.includes('download')) {
            title = 'Report Export';
            icon = 'ph-file-arrow-down';
          } else if (lower.includes('success') || lower.includes('posted')) {
            title = 'Action Completed';
            icon = 'ph-check-circle';
          } else if (lower.includes('warning') || lower.includes('error')) {
            title = 'System Alert';
            icon = 'ph-warning';
          }
        }
        window.showSystemModal(message, title, icon);
      };

      window.showToast = function(message, type = 'success') {
        const container = document.getElementById('toast-container');
        if (!container) return;
        const toast = document.createElement('div');
        const isSuccess = type === 'success';
        const isError = type === 'error' || type === 'danger';
        const isWarning = type === 'warning';
        const bg = isSuccess ? 'bg-emerald-600 text-white shadow-emerald-900/30' : (isError ? 'bg-rose-600 text-white shadow-rose-900/30' : (isWarning ? 'bg-amber-600 text-white shadow-amber-900/30' : 'bg-slate-900 text-white shadow-slate-900/30'));
        const icon = isSuccess ? 'ph-check-circle' : (isError ? 'ph-x-circle' : (isWarning ? 'ph-warning' : 'ph-info'));
        toast.className = `pointer-events-auto flex items-center gap-2.5 px-4 py-3 rounded-2xl shadow-xl ring-1 ring-white/10 ${bg} transition-all duration-300 transform translate-y-2 opacity-0 text-xs font-semibold max-w-sm`;
        toast.innerHTML = `<i class="ph-bold ${icon} text-lg flex-shrink-0"></i><span>${message}</span>`;
        container.appendChild(toast);
        requestAnimationFrame(() => {
          toast.classList.remove('translate-y-2', 'opacity-0');
        });
        setTimeout(() => {
          toast.classList.add('opacity-0', 'translate-y-2');
          setTimeout(() => toast.remove(), 300);
        }, 3500);
      };
    </script>

    <!-- Session Security & Idle Timeout Monitor -->
    <script src="{{ asset('assets/js/auth/idle-monitor.js') }}"></script>

    <!-- Executive Navigation & Submodule Loading Modal Controller -->
    <script>
      (function() {
        const loader = document.getElementById('fms-navigation-loader');
        const backdrop = document.getElementById('fms-loader-backdrop');
        const card = document.getElementById('fms-loader-card');
        const titleEl = document.getElementById('fms-loader-title');
        const descEl = document.getElementById('fms-loader-desc');

        let safetyTimeout = null;

        function showNavigationLoader(customTitle = null, customDesc = null) {
          if (!loader || !backdrop || !card) return;

          if (customTitle && titleEl) {
            titleEl.textContent = customTitle;
          } else if (titleEl) {
            titleEl.textContent = 'Loading Module...';
          }

          if (customDesc && descEl) {
            descEl.textContent = customDesc;
          } else if (descEl) {
            descEl.textContent = 'Preparing hospital ledger & clinical records';
          }

          clearTimeout(safetyTimeout);

          // Make visible and activate transitions
          loader.classList.remove('pointer-events-none', 'invisible', 'opacity-0');
          loader.classList.add('pointer-events-auto', 'opacity-100');

          requestAnimationFrame(() => {
            backdrop.classList.remove('opacity-0');
            backdrop.classList.add('opacity-100');

            card.classList.remove('opacity-0', 'scale-95');
            card.classList.add('opacity-100', 'scale-100');
          });

          // Safety auto-dismiss after 8 seconds in case navigation is interrupted or downloads a file
          safetyTimeout = setTimeout(() => {
            hideNavigationLoader();
          }, 8000);
        }

        function hideNavigationLoader() {
          if (!loader || !backdrop || !card) return;
          clearTimeout(safetyTimeout);

          backdrop.classList.remove('opacity-100');
          backdrop.classList.add('opacity-0');

          card.classList.remove('opacity-100', 'scale-100');
          card.classList.add('opacity-0', 'scale-95');

          setTimeout(() => {
            loader.classList.remove('pointer-events-auto', 'opacity-100');
            loader.classList.add('pointer-events-none', 'invisible', 'opacity-0');
          }, 200);

          document.querySelectorAll('.sidebar-link.is-navigating').forEach(el => el.classList.remove('is-navigating'));
        }

        // Export globally for programmatic calls (e.g., reports, async exports)
        window.showNavigationLoader = showNavigationLoader;
        window.hideNavigationLoader = hideNavigationLoader;

        // Reset loader on page restore from BFCache and history navigation
        window.addEventListener('pageshow', hideNavigationLoader);
        window.addEventListener('popstate', hideNavigationLoader);

        // Allow dismissing via Escape key if navigation hangs
        window.addEventListener('keydown', function(e) {
          if (e.key === 'Escape') hideNavigationLoader();
        });

        // Intercept navigation link clicks (submodules, sidebar links, header actions)
        document.addEventListener('click', function(e) {
          const link = e.target.closest('a');
          if (!link) return;

          // Don't intercept if prevented or non-primary/modifier clicks
          if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
          if (link.target && link.target !== '_self') return;

          const hrefAttr = link.getAttribute('href');
          if (!hrefAttr || hrefAttr.startsWith('#') || hrefAttr.startsWith('javascript:') || hrefAttr.startsWith('mailto:') || hrefAttr.startsWith('tel:')) return;
          if (link.hasAttribute('download')) return;
          if (link.dataset.noLoader !== undefined) return;

          try {
            const url = new URL(link.href, window.location.origin);

            // Only trigger for same-origin URLs
            if (url.origin !== window.location.origin) return;

            // Ignore pure in-page fragment hashes
            if (url.pathname === window.location.origin && url.hash) return;
            if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash) return;

            // Extract context-aware title from link (e.g. submodule text)
            let labelText = '';
            const truncateEl = link.querySelector('.truncate, span:not(.sidebar-caret), h4, p');
            if (truncateEl && truncateEl.textContent.trim()) {
              labelText = truncateEl.textContent.trim();
            } else if (link.getAttribute('title')) {
              labelText = link.getAttribute('title').trim();
            } else {
              const clone = link.cloneNode(true);
              clone.querySelectorAll('i, svg').forEach(n => n.remove());
              const text = clone.textContent.trim();
              if (text && text.length < 35) {
                labelText = text;
              }
            }

            const title = labelText ? `Loading ${labelText}...` : 'Loading Module...';
            const isSidebar = Boolean(link.closest('#fms-sidebar'));
            const desc = isSidebar ? 'Navigating hospital module...' : 'Preparing financial records...';

            showNavigationLoader(title, desc);

            if (link.classList.contains('sidebar-link')) {
              link.classList.add('is-navigating');
            }
          } catch (err) {}
        });

        // Also intercept full-page form submissions (e.g. search, filters, non-AJAX actions)
        document.addEventListener('submit', function(e) {
          const form = e.target;
          if (!form || form.dataset.noLoader !== undefined || form.target === '_blank') return;
          if (e.defaultPrevented) return;
          if (form.id === 'idle-logout-form') return; // Idle logout has its own workflow

          showNavigationLoader('Processing...', 'Submitting hospital transaction securely...');
        });
      })();
    </script>

    @stack('scripts')
  </body>
</html>

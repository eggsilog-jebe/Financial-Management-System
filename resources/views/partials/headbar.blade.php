<header class="sticky top-0 z-30 flex h-14 sm:h-16 w-full items-center justify-between border-b border-slate-200/80 bg-white/90 px-6 lg:px-8 backdrop-blur-md dark:border-slate-800 dark:bg-slate-900/90 transition-colors">
  <!-- Left: Mobile Toggle (Mobile Only) -->
  <div class="flex items-center gap-3">
    <button 
      @click="sidebarOpen = !sidebarOpen" 
      type="button" 
      class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-slate-500 hover:bg-slate-100 hover:text-slate-900 lg:hidden dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white transition-colors"
      aria-label="Toggle navigation drawer"
    >
      <i class="ph-bold ph-list text-xl"></i>
    </button>
  </div>

<script>
  window.globalSearchEngine = function(config) {
    return {
      query: '',
      isOpen: false,
      loading: false,
      searchUrl: config.searchUrl || '',
      predictions: [],
      selectedIndex: -1,
      debounceTimer: null,

      init() {
        window.addEventListener('keydown', (e) => {
          if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            this.$refs.searchInput.focus();
            this.$refs.searchInput.select();
          }
          if (e.key === 'Escape') {
            this.closeSearch();
          }
        });
      },

      closeSearch() {
        this.isOpen = false;
        this.selectedIndex = -1;
      },

      clearSearch() {
        this.query = '';
        this.predictions = [];
        this.selectedIndex = -1;
        this.closeSearch();
        this.$refs.searchInput.focus();
      },

      onInput() {
        const clean = this.query.trim();
        if (clean.length === 0) {
          this.predictions = [];
          this.selectedIndex = -1;
          this.isOpen = false;
          clearTimeout(this.debounceTimer);
          return;
        }

        this.isOpen = true;
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(() => {
          this.fetchPredictions(clean);
        }, 120);
      },

      async fetchPredictions(term) {
        if (!term) return;
        this.loading = true;
        try {
          const params = new URLSearchParams({ q: term });
          const res = await fetch(`${this.searchUrl}?${params.toString()}`, {
            headers: {
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            }
          });
          if (!res.ok) return;
          const json = await res.json();
          if (json.success && json.data) {
            this.predictions = json.data.predictions || [];
            this.selectedIndex = this.predictions.length > 0 ? 0 : -1;
          }
        } catch (e) {
          console.error('Prediction fetch error:', e);
        } finally {
          this.loading = false;
        }
      },

      navigate(step) {
        if (!this.isOpen || this.predictions.length === 0) return;
        this.selectedIndex = (this.selectedIndex + step + this.predictions.length) % this.predictions.length;
        this.scrollToSelected();
      },

      scrollToSelected() {
        this.$nextTick(() => {
          const el = document.getElementById(`search-pred-item-${this.selectedIndex}`);
          if (el) {
            el.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
          }
        });
      },

      selectCurrent() {
        if (this.selectedIndex >= 0 && this.predictions[this.selectedIndex]) {
          window.location.href = this.predictions[this.selectedIndex].url;
          return;
        }
        if (this.predictions.length > 0) {
          window.location.href = this.predictions[0].url;
        }
      }
    };
  };
</script>

  <!-- Center: Predictive Search Bar with Ctrl+K shortcut -->
  <div 
    class="hidden md:flex flex-1 max-w-md mx-4 relative" 
    x-data="globalSearchEngine({
      searchUrl: '{{ route('global.search') }}'
    })"
    @click.outside="closeSearch()"
  >
    <div class="relative w-full">
      <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
        <template x-if="!loading">
          <i class="ph-bold ph-magnifying-glass text-base"></i>
        </template>
        <template x-if="loading">
          <i class="ph-bold ph-spinner animate-spin text-emerald-600 dark:text-emerald-400 text-base"></i>
        </template>
      </div>

      <input 
        x-ref="searchInput"
        type="search" 
        x-model="query"
        @input="onInput()"
        @keydown.down.prevent="navigate(1)"
        @keydown.up.prevent="navigate(-1)"
        @keydown.enter.prevent="selectCurrent()"
        @keydown.escape.prevent="closeSearch()"
        placeholder="Search..." 
        style="outline: none !important; -webkit-tap-highlight-color: transparent;"
        class="w-full rounded-xl border-0 bg-slate-100/90 py-2 pl-10 pr-16 text-xs sm:text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-emerald-600 focus:outline-none focus:outline-0 outline-none dark:bg-slate-800 dark:text-white dark:ring-slate-700 transition-all shadow-sm"
        autocomplete="off"
        spellcheck="false"
      >

      <!-- Right: Clear Button & Ctrl+K badge -->
      <div class="absolute inset-y-0 right-0 flex items-center pr-2.5 gap-1.5">
        <template x-if="query.length > 0">
          <button 
            type="button" 
            @click="clearSearch()"
            class="p-1 rounded-md text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-200/60 dark:hover:bg-slate-700/60 transition-colors"
            title="Clear search"
          >
            <i class="ph-bold ph-x text-xs"></i>
          </button>
        </template>

        <kbd class="hidden sm:inline-flex items-center rounded border border-slate-300 bg-slate-50 px-1.5 font-mono text-[10px] font-medium text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400 select-none">
          Ctrl K
        </kbd>
      </div>
    </div>

    <!-- Predictive Autocomplete Suggestions Palette: ONLY visible when typing (query.trim().length > 0) -->
    <div 
      x-show="isOpen && query.trim().length > 0" 
      x-cloak 
      x-transition:enter="transition ease-out duration-100"
      x-transition:enter-start="opacity-0 translate-y-1 scale-98"
      x-transition:enter-end="opacity-100 translate-y-0 scale-100"
      x-transition:leave="transition ease-in duration-75"
      x-transition:leave-start="opacity-100 translate-y-0 scale-100"
      x-transition:leave-end="opacity-0 translate-y-1 scale-98"
      class="absolute top-full mt-1.5 w-full min-w-[340px] max-w-lg left-0 z-50 rounded-xl bg-white dark:bg-slate-900 shadow-xl ring-1 ring-slate-200 dark:ring-slate-800 overflow-hidden divide-y divide-slate-100 dark:divide-slate-800"
    >
      <!-- Suggestions List -->
      <div class="max-h-[320px] overflow-y-auto custom-scrollbar p-1.5 space-y-0.5">
        <template x-for="(item, idx) in predictions" :key="'pred-' + idx">
          <a 
            :href="item.url" 
            :id="'search-pred-item-' + idx"
            @mouseenter="selectedIndex = idx"
            class="flex items-center justify-between gap-3 px-3 py-2 rounded-lg transition-colors cursor-pointer group"
            :class="selectedIndex === idx ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-900 dark:text-emerald-100' : 'hover:bg-slate-50 dark:hover:bg-slate-800/60 text-slate-800 dark:text-slate-200'"
          >
            <div class="flex items-center gap-2.5 min-w-0">
              <div 
                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-sm transition-colors"
                :class="selectedIndex === idx ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400 group-hover:text-emerald-600 dark:group-hover:text-emerald-400'"
              >
                <i :class="'ph-bold ' + item.icon"></i>
              </div>
              <div class="min-w-0">
                <div class="text-xs font-medium truncate" x-text="item.title"></div>
                <div 
                  class="text-[10px] truncate"
                  :class="selectedIndex === idx ? 'text-emerald-700/80 dark:text-emerald-300/80' : 'text-slate-400 dark:text-slate-500'"
                  x-text="item.subtitle"
                ></div>
              </div>
            </div>
            <div class="shrink-0 flex items-center gap-1.5">
              <span 
                class="rounded-md px-1.5 py-0.5 text-[10px] font-medium transition-colors"
                :class="selectedIndex === idx ? 'bg-emerald-600/10 text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-300' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400'"
                x-text="item.badge"
              ></span>
            </div>
          </a>
        </template>

        <!-- Empty state when no predictions found -->
        <template x-if="!loading && predictions.length === 0">
          <div class="py-4 px-3 text-center text-xs text-slate-400 dark:text-slate-500">
            No suggestions found for "<span class="font-medium text-slate-700 dark:text-slate-300" x-text="query"></span>"
          </div>
        </template>
      </div>

      <!-- Subtle bottom navigation hint -->
      <div class="px-3 py-1.5 bg-slate-50/70 dark:bg-slate-800/40 flex items-center justify-between text-[10px] text-slate-400">
        <span class="flex items-center gap-1.5">
          <kbd class="px-1 py-0.5 rounded bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 font-mono text-[9px]">↑↓</kbd> navigate
          <kbd class="px-1 py-0.5 rounded bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 font-mono text-[9px]">↵</kbd> select
        </span>
        <span x-text="predictions.length + ' suggestion' + (predictions.length === 1 ? '' : 's')"></span>
      </div>
    </div>
  </div>

  <!-- Right: Shift Status, Workstation Indicator, Theme Toggle, Profile -->
  <div class="flex items-center gap-2 sm:gap-3">
    <!-- Live System Alerts Bell Notification Hub -->
    <div 
      class="relative" 
      x-data="{
        alertsOpen: false,
        alerts: {{ \Illuminate\Support\Js::from($systemAlertsData['alerts'] ?? []) }},
        alertCount: {{ (int) ($systemAlertsData['count'] ?? 0) }},
        severity: '{{ $systemAlertsData['highest_severity'] ?? 'none' }}',
        isSyncing: false,
        lastSynced: 'Just now',
        pollingInterval: null,

        init() {
          this.pollingInterval = setInterval(() => {
            this.fetchLiveAlerts();
          }, 30000);
        },

        async fetchLiveAlerts() {
          try {
            const res = await fetch('{{ route('system-alerts.feed') }}', {
              headers: { 
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
              }
            });
            if (!res.ok) return;
            const data = await res.json();
            if (data.success && data.data) {
              this.alerts = data.data.alerts || [];
              this.alertCount = data.data.count || 0;
              this.severity = data.data.highest_severity || 'none';
              const now = new Date();
              this.lastSynced = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            }
          } catch (e) {
            // Silently handle offline/network blips
          }
        },

        async dismissAlert(alertId, ackUrl) {
          this.isSyncing = true;
          try {
            const res = await fetch(ackUrl || '{{ route('system-alerts.acknowledge') }}', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
              }
            });
            if (res.ok) {
              const data = await res.json();
              if (data.data) {
                this.alerts = data.data.alerts || [];
                this.alertCount = data.data.count || 0;
                this.severity = data.data.highest_severity || 'none';
              } else {
                this.alerts = this.alerts.filter(a => a.id !== alertId);
                this.alertCount = this.alerts.length;
                this.severity = this.alerts.some(a => a.severity === 'critical') ? 'critical' : (this.alerts.length > 0 ? 'warning' : 'none');
              }
            }
          } catch (e) {
            console.error('Error acknowledging alert:', e);
          } finally {
            this.isSyncing = false;
          }
        }
      }"
      @click.outside="alertsOpen = false"
      @keydown.escape.window="alertsOpen = false"
    >
      <!-- Bell Notification Trigger with Mild Glow if Updates Exist -->
      <button 
        type="button" 
        @click="alertsOpen = !alertsOpen" 
        class="relative inline-flex h-9 w-9 items-center justify-center rounded-xl transition-all duration-200 focus:outline-none"
        :class="{
          'animate-mild-glow-rose text-rose-600 bg-rose-50/90 dark:bg-rose-950/40 dark:text-rose-300 ring-1 ring-rose-400/50 shadow-sm': alertCount > 0 && severity === 'critical',
          'animate-mild-glow-amber text-amber-600 bg-amber-50/90 dark:bg-amber-950/40 dark:text-amber-300 ring-1 ring-amber-400/50 shadow-sm': alertCount > 0 && severity !== 'critical',
          'text-slate-500 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white': alertCount === 0
        }"
        :title="alertCount > 0 ? (alertCount + ' active system alert' + (alertCount > 1 ? 's' : '')) : 'Live system alerts: all systems normal'"
        :aria-label="alertCount > 0 ? (alertCount + ' active system alerts') : 'Live system alerts'"
        :aria-expanded="alertsOpen.toString()"
      >
        <!-- Bell Icon -->
        <i class="ph-bold text-lg" :class="alertCount > 0 ? 'ph-bell-ringing' : 'ph-bell'" aria-hidden="true"></i>

        <!-- Mild Ping / Indicator Badge if updates exist -->
        <template x-if="alertCount > 0">
          <span class="absolute -top-1 -right-1 flex h-4 min-w-[16px] items-center justify-center rounded-full px-1 text-[9px] font-extrabold text-white shadow-sm ring-2 ring-white dark:ring-slate-900"
                :class="severity === 'critical' ? 'bg-rose-600' : 'bg-amber-500'">
            <span class="absolute inline-flex h-full w-full rounded-full opacity-75 animate-ping"
                  :class="severity === 'critical' ? 'bg-rose-400' : 'bg-amber-400'"></span>
            <span class="relative" x-text="alertCount"></span>
          </span>
        </template>
      </button>

      <!-- Dropdown Popover Panel -->
      <div 
        x-show="alertsOpen" 
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="transform opacity-0 scale-95 -translate-y-1"
        x-transition:enter-end="transform opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="transform opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="transform opacity-0 scale-95 -translate-y-1"
        class="absolute right-0 mt-2 w-80 sm:w-96 max-w-sm sm:max-w-md origin-top-right rounded-2xl bg-white p-3.5 shadow-2xl ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800 z-50 divide-y divide-slate-100 dark:divide-slate-800"
      >
        <!-- Header -->
        <div class="flex items-center justify-between pb-2.5">
          <div class="flex items-center gap-2">
            <span class="inline-flex h-2 w-2 rounded-full"
                  :class="alertCount > 0 ? (severity === 'critical' ? 'bg-rose-500 animate-pulse' : 'bg-amber-500 animate-pulse') : 'bg-emerald-500'"></span>
            <div>
              <p class="text-xs font-bold text-slate-900 dark:text-white leading-tight">Live System Alerts</p>
              <p class="text-[10px] text-slate-500 dark:text-slate-400 leading-tight">Hospital Security &amp; Ledger Hub</p>
            </div>
          </div>
          <div class="flex items-center gap-1.5">
            <template x-if="alertCount > 0">
              <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold"
                    :class="severity === 'critical' ? 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300'"
                    x-text="alertCount + ' Active'">
              </span>
            </template>
            <template x-if="alertCount === 0">
              <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300">
                <i class="ph-bold ph-check text-[10px]"></i>
                <span>Normal</span>
              </span>
            </template>
            <button 
              type="button" 
              @click="fetchLiveAlerts()" 
              title="Refresh alerts"
              class="inline-flex h-6 w-6 items-center justify-center rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-slate-800 transition-colors"
            >
              <i class="ph-bold ph-arrows-clockwise text-xs" :class="{ 'animate-spin': isSyncing }"></i>
            </button>
          </div>
        </div>

        <!-- Alert Items List -->
        <div class="py-2.5 space-y-2.5 max-h-[340px] overflow-y-auto custom-scrollbar">
          <!-- Active Alerts -->
          <template x-for="item in alerts" :key="item.id">
            <div 
              class="rounded-xl p-3 transition-all"
              :class="{
                'bg-rose-50/80 border border-rose-200/80 dark:bg-rose-950/30 dark:border-rose-900/60': item.severity === 'critical',
                'bg-amber-50/80 border border-amber-200/80 dark:bg-amber-950/30 dark:border-amber-900/60': item.severity === 'warning',
                'bg-slate-50 border border-slate-200/80 dark:bg-slate-800/50 dark:border-slate-700': item.severity !== 'critical' && item.severity !== 'warning'
              }"
            >
              <div class="flex items-start gap-2.5">
                <span 
                  class="inline-flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-lg text-xs"
                  :class="{
                    'bg-rose-100 text-rose-600 dark:bg-rose-900/50 dark:text-rose-300': item.severity === 'critical',
                    'bg-amber-100 text-amber-600 dark:bg-amber-900/50 dark:text-amber-300': item.severity === 'warning',
                    'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200': item.severity !== 'critical' && item.severity !== 'warning'
                  }"
                >
                  <i class="ph-bold" :class="item.icon || 'ph-warning'"></i>
                </span>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center justify-between gap-1">
                    <p 
                      class="text-xs font-bold truncate leading-tight"
                      :class="item.severity === 'critical' ? 'text-rose-950 dark:text-rose-200' : 'text-amber-950 dark:text-amber-200'"
                      x-text="item.title"
                    ></p>
                    <span class="text-[10px] text-slate-400 dark:text-slate-500 shrink-0" x-text="item.time"></span>
                  </div>
                  <p 
                    class="mt-1 text-[11px] leading-relaxed"
                    :class="item.severity === 'critical' ? 'text-rose-700/90 dark:text-rose-300/90' : 'text-amber-700/90 dark:text-amber-300/90'"
                    x-text="item.description"
                  ></p>

                  <!-- Quick Action Buttons -->
                  <div class="mt-2.5 flex items-center gap-1.5 flex-wrap">
                    <template x-if="item.can_acknowledge">
                      <button 
                        type="button" 
                        @click="dismissAlert(item.id, item.acknowledge_url)"
                        :disabled="isSyncing"
                        class="inline-flex items-center gap-1 rounded-lg border border-emerald-300/90 bg-emerald-50 px-2.5 py-1 text-[10px] font-semibold text-emerald-700 shadow-xs hover:bg-emerald-100 hover:border-emerald-400 dark:border-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 dark:hover:bg-emerald-900/60 transition-colors"
                      >
                        <i class="ph-bold ph-check text-[11px] text-emerald-600 dark:text-emerald-400"></i>
                        <span>Acknowledge</span>
                      </button>
                    </template>
                    <template x-if="item.action_url">
                      <a 
                        :href="item.action_url" 
                        class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-[10px] font-semibold text-white shadow-xs transition-colors"
                        :class="item.severity === 'critical' ? 'bg-rose-600 hover:bg-rose-700' : 'bg-amber-600 hover:bg-amber-700'"
                      >
                        <i class="ph-bold ph-arrow-square-out"></i>
                        <span x-text="item.action_text"></span>
                      </a>
                    </template>
                  </div>
                </div>
              </div>
            </div>
          </template>

          <!-- Empty State when 0 alerts -->
          <template x-if="alertCount === 0">
            <div class="py-6 px-4 text-center">
              <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300 mx-auto">
                <i class="ph-bold ph-shield-check text-2xl"></i>
              </span>
              <p class="mt-2.5 text-xs font-bold text-slate-800 dark:text-white">All Systems Operational</p>
              <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400 max-w-[240px] mx-auto">
                No active security incidents, unauthorized terminals, or critical budget exceptions.
              </p>
              <div class="mt-3 inline-flex items-center gap-1 text-[10px] text-slate-400 dark:text-slate-500">
                <i class="ph-bold ph-clock text-[10px]"></i>
                <span>Synced: <span x-text="lastSynced"></span></span>
              </div>
            </div>
          </template>
        </div>
      </div>
    </div>

    <!-- Theme Toggle Button (Light by default) -->
    <button 
      type="button" 
      @click="
        if (typeof window.setFmsTheme === 'function') {
          window.setFmsTheme(darkMode ? 'light' : 'dark');
        } else {
          darkMode = !darkMode;
          if (darkMode) {
            document.documentElement.classList.add('dark');
            localStorage.setItem('fms_theme', 'dark');
            localStorage.setItem('himsMainTheme', 'dark');
          } else {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('fms_theme', 'light');
            localStorage.setItem('himsMainTheme', 'light');
          }
        }
      " 
      class="group relative inline-flex h-9 w-9 items-center justify-center rounded-xl text-slate-500 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white transition-all overflow-hidden cursor-pointer"
      :title="darkMode ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
      :aria-label="darkMode ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
      id="btn-theme-toggle"
    >
      {{-- Moon Icon (Visible in Light Mode, morphs out in Dark Mode) --}}
      <span class="inline-flex items-center justify-center transition-all duration-300 transform dark:-rotate-90 dark:scale-0 dark:opacity-0 rotate-0 scale-100 opacity-100">
        <i class="ph-bold ph-moon text-lg text-slate-600 group-hover:text-slate-900" aria-hidden="true"></i>
      </span>
      {{-- Sun Icon (Visible in Dark Mode, morphs in from 90deg) --}}
      <span class="absolute inline-flex items-center justify-center transition-all duration-300 transform rotate-90 scale-0 opacity-0 dark:rotate-0 dark:scale-100 dark:opacity-100">
        <i class="ph-bold ph-sun text-lg text-amber-400 group-hover:text-amber-300" aria-hidden="true"></i>
      </span>
    </button>

    <!-- Executive User Profile Menu (Alpine.js) -->
    <div 
      class="relative pl-2 border-l border-slate-200 dark:border-slate-800" 
      x-data="{
        userMenuOpen: false,
        userName: '{{ addslashes(auth()->user()->name ?? 'Executive') }}',
        userEmail: '{{ addslashes(auth()->user()->email ?? 'user@hospital.gov.ph') }}',
        userAvatar: '{{ auth()->user()->avatarUrl() ?? '' }}'
      }"
      @user-profile-updated.window="
        if ($event.detail.name) userName = $event.detail.name;
        if ($event.detail.email) userEmail = $event.detail.email;
        if ($event.detail.avatarUrl !== undefined) userAvatar = $event.detail.avatarUrl;
      "
    >
      <button 
        type="button" 
        @click="userMenuOpen = !userMenuOpen" 
        @click.outside="userMenuOpen = false"
        class="flex items-center gap-2 rounded-xl p-1 text-left transition-colors hover:bg-slate-100 dark:hover:bg-slate-800 focus:outline-none"
        aria-expanded="false"
        :aria-expanded="userMenuOpen.toString()"
      >
        <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300 ring-1 ring-slate-200 dark:ring-slate-700 overflow-hidden shrink-0">
          <template x-if="userAvatar">
            <img :src="userAvatar" :alt="userName" class="h-full w-full object-cover">
          </template>
          <template x-if="!userAvatar">
            <i class="ph-bold ph-user text-sm"></i>
          </template>
        </span>
        <div class="hidden sm:flex flex-col text-left">
          <span class="text-xs font-semibold text-slate-900 dark:text-white max-w-[120px] truncate leading-tight" x-text="userName">
            {{ auth()->user()->name ?? 'Executive' }}
          </span>
          <span class="text-[10px] font-medium text-slate-500 dark:text-slate-400 leading-tight">
            {{ auth()->user()->role ?? 'Hospital Staff' }}
          </span>
        </div>
        <i class="ph-bold ph-caret-down text-xs text-slate-400 transition-transform" :class="{ 'rotate-180': userMenuOpen }"></i>
      </button>

      <!-- Dropdown Panel -->
      <div 
        x-show="userMenuOpen" 
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute right-0 mt-2 w-64 origin-top-right rounded-2xl bg-white p-2 shadow-2xl ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800 z-50 divide-y divide-slate-100 dark:divide-slate-800"
      >
        <div class="px-3 py-2.5">
          <p class="text-xs font-bold text-slate-900 dark:text-white truncate" x-text="userName">{{ auth()->user()->name ?? 'Executive' }}</p>
          <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate" x-text="userEmail">{{ auth()->user()->email ?? 'user@hospital.gov.ph' }}</p>
          <div class="mt-2 inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300">
            <i class="ph-bold ph-shield-check"></i>
            <span>{{ auth()->user()->role ?? 'Finance' }}</span>
          </div>
        </div>

        <div class="py-1">
          <a href="{{ route('account.settings') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800 transition-colors">
            <i class="ph-bold ph-gear text-slate-400"></i>
            <span>Account Settings</span>
          </a>
        </div>

        <div class="pt-1">
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full flex items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/30 transition-colors">
              <i class="ph-bold ph-sign-out text-base"></i>
              <span>Sign Out of Terminal</span>
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</header>

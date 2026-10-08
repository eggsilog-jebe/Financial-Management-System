@php
  $isDashboard = request()->routeIs('accounting.dashboard') || request()->routeIs('dashboard');
  $isGl = request()->routeIs('gl.*') || request()->routeIs('accounting.general-ledger.*') || request()->routeIs('accounting.period-close.*');
  $isAp = request()->routeIs('ap.*');
  $isAr = request()->routeIs('ar.*');
  $isDisbursement = request()->routeIs('disbursement.*');
  $isCollection = request()->routeIs('collection.*') || request()->routeIs('accounting.cashier.*');
  $isBudget = request()->routeIs('budget.*');
  $isUserSecurity = request()->routeIs('user-security.*') || request()->routeIs('accounting.audit-log');
@endphp

<!-- Mobile Backdrop -->
<div 
  x-show="sidebarOpen" 
  x-cloak
  @click="sidebarOpen = false" 
  x-transition.opacity.duration.200ms 
  class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-md lg:hidden transition-opacity"
  aria-hidden="true"
></div>

<aside 
  id="fms-sidebar"
  :class="[
    sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
    sidebarCollapsed ? 'lg:w-20' : 'lg:w-64'
  ]"
  class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-white border-r border-slate-200/80 dark:bg-slate-900 dark:border-slate-800 lg:static lg:translate-x-0 select-none shadow-sm lg:shadow-none overflow-hidden"
  aria-label="Primary navigation"
>
  <!-- Brand Header -->
  <div 
    class="flex h-16 flex-shrink-0 items-center border-b border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 transition-all duration-300 ease-in-out px-4 overflow-hidden"
    :class="sidebarCollapsed ? 'lg:justify-center' : 'justify-between'"
  >
    <!-- Brand Info (Visible when expanded or on mobile) -->
    <a 
      href="{{ url('/') }}" 
      class="flex items-center gap-3 min-w-0 group transition-all duration-300 ease-in-out overflow-hidden"
      :class="sidebarCollapsed ? 'lg:w-0 lg:opacity-0 lg:pointer-events-none' : 'w-auto opacity-100'"
    >
      <div class="relative flex h-9 w-9 shrink-0 items-center justify-center rounded-xl overflow-hidden shadow-xs ring-1 ring-slate-900/5 dark:ring-white/10 group-hover:scale-105 transition-transform duration-200">
        <img src="{{ asset('favicon.svg') }}" alt="Hospital FMS" class="h-full w-full object-contain select-none">
      </div>
      <div 
        class="flex flex-col min-w-0 transition-all duration-300 ease-in-out overflow-hidden whitespace-nowrap"
        :class="sidebarCollapsed ? 'lg:max-w-0 lg:opacity-0' : 'max-w-[150px] opacity-100'"
      >
        <div class="flex items-center gap-1.5 leading-tight">
          <span class="font-bold text-sm tracking-tight text-slate-900 dark:text-white">Hospital FMS</span>
        </div>
        <span class="text-[11px] font-medium text-slate-400 dark:text-slate-500 truncate">Financial Management</span>
      </div>
    </a>

    <!-- Unified Desktop Burger Button (Toggles minimize and expand) -->
    <button 
      type="button" 
      @click="toggleSidebarCollapse()" 
      class="sidebar-toggle-btn hidden lg:inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-500 hover:text-slate-900 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800 transition-all duration-300 ease-in-out focus:outline-none" 
      :title="sidebarCollapsed ? 'Expand sidebar (Ctrl+B)' : 'Minimize sidebar (Ctrl+B)'" 
      :aria-label="sidebarCollapsed ? 'Expand sidebar' : 'Minimize sidebar'"
    >
      <i class="ph-bold ph-list text-xl"></i>
    </button>

    <!-- Mobile Close Button (Mobile Only) -->
    <button 
      type="button" 
      @click="sidebarOpen = false" 
      class="rounded-lg p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-slate-800 lg:hidden transition-colors" 
      aria-label="Close navigation"
    >
      <i class="ph-bold ph-x text-base"></i>
    </button>
  </div>

  <!-- Navigation Links -->
  <nav 
    class="flex-1 overflow-y-auto py-4 space-y-1 custom-scrollbar transition-all duration-300 ease-in-out" 
    :class="sidebarCollapsed ? 'lg:px-2 px-3.5' : 'px-3.5'"
    aria-label="Main menu"
  >
    
    <!-- 0. Dashboard -->
    @can('access-general-ledger')
    <a 
      href="{{ route('accounting.dashboard') }}" 
      class="sidebar-link group relative flex items-center rounded-xl text-sm font-medium transition-colors duration-150 py-2.5 overflow-hidden {{ $isDashboard ? 'bg-emerald-50 text-emerald-800 font-semibold ring-1 ring-emerald-600/20 dark:bg-emerald-950/50 dark:text-emerald-300 dark:ring-emerald-500/30' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800/60' }}"
      :class="sidebarCollapsed ? 'lg:px-0 lg:justify-center' : 'px-3 justify-between'"
    >
      <div class="flex items-center min-w-0 transition-all duration-300 ease-in-out" :class="sidebarCollapsed ? 'lg:gap-0' : 'gap-3'">
        <i class="ph-bold ph-squares-four text-lg shrink-0 {{ $isDashboard ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300' }}"></i>
        <span 
          class="sidebar-label whitespace-nowrap overflow-hidden transition-all duration-300 ease-in-out"
          :class="sidebarCollapsed ? 'lg:max-w-0 lg:opacity-0 lg:-translate-x-2' : 'max-w-[160px] opacity-100 translate-x-0'"
        >Dashboard</span>
      </div>
      @if($isDashboard)
        <span 
          class="sidebar-indicator h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400 shrink-0 transition-all duration-300 ease-in-out"
          :class="sidebarCollapsed ? 'lg:opacity-0 lg:scale-0' : 'opacity-100 scale-100'"
        ></span>
      @endif

      <!-- Floating Tooltip on Hover (Desktop Collapsed Mode) -->
      <div 
        x-show="sidebarCollapsed" 
        x-cloak 
        class="hidden lg:group-hover:flex absolute left-full ml-3 top-1/2 -translate-y-1/2 z-50 items-center px-2.5 py-1.5 rounded-xl bg-slate-900 text-white text-xs font-semibold shadow-xl whitespace-nowrap pointer-events-none ring-1 ring-slate-800 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
      >
        <span>Dashboard</span>
        <span class="absolute right-full top-1/2 -translate-y-1/2 border-4 border-transparent border-r-slate-900 dark:border-r-slate-800"></span>
      </div>
    </a>
    @endcan

    <!-- 1. Accounts Payable (AP) -->
    @can('access-ap-procurement')
    <a 
      href="{{ route('ap.vendors') }}" 
      class="sidebar-link group relative flex items-center rounded-xl text-sm font-medium transition-colors duration-150 py-2.5 overflow-hidden {{ $isAp ? 'bg-emerald-50 text-emerald-800 font-semibold ring-1 ring-emerald-600/20 dark:bg-emerald-950/50 dark:text-emerald-300 dark:ring-emerald-500/30' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/70 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800/50' }}"
      :class="sidebarCollapsed ? 'lg:px-0 lg:justify-center' : 'px-3 justify-between'"
    >
      <div class="flex items-center min-w-0 transition-all duration-300 ease-in-out" :class="sidebarCollapsed ? 'lg:gap-0' : 'gap-3'">
        <i class="ph-bold ph-receipt text-lg shrink-0 {{ $isAp ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300' }}"></i>
        <span 
          class="sidebar-label whitespace-nowrap overflow-hidden transition-all duration-300 ease-in-out"
          :class="sidebarCollapsed ? 'lg:max-w-0 lg:opacity-0 lg:-translate-x-2' : 'max-w-[160px] opacity-100 translate-x-0'"
        >Accounts Payable</span>
      </div>
      @if($isAp)
        <span 
          class="sidebar-indicator h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400 shrink-0 transition-all duration-300 ease-in-out"
          :class="sidebarCollapsed ? 'lg:opacity-0 lg:scale-0' : 'opacity-100 scale-100'"
        ></span>
      @endif

      <!-- Floating Tooltip on Hover (Desktop Collapsed Mode) -->
      <div 
        x-show="sidebarCollapsed" 
        x-cloak 
        class="hidden lg:group-hover:flex absolute left-full ml-3 top-1/2 -translate-y-1/2 z-50 items-center px-2.5 py-1.5 rounded-xl bg-slate-900 text-white text-xs font-semibold shadow-xl whitespace-nowrap pointer-events-none ring-1 ring-slate-800 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
      >
        <span>Accounts Payable</span>
        <span class="absolute right-full top-1/2 -translate-y-1/2 border-4 border-transparent border-r-slate-900 dark:border-r-slate-800"></span>
      </div>
    </a>
    @endcan

    <!-- 2. Accounts Receivable (AR) -->
    @can('access-ar-billing')
    <a 
      href="{{ route('ar.billing') }}" 
      class="sidebar-link group relative flex items-center rounded-xl text-sm font-medium transition-colors duration-150 py-2.5 overflow-hidden {{ $isAr ? 'bg-emerald-50 text-emerald-800 font-semibold ring-1 ring-emerald-600/20 dark:bg-emerald-950/50 dark:text-emerald-300 dark:ring-emerald-500/30' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/70 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800/50' }}"
      :class="sidebarCollapsed ? 'lg:px-0 lg:justify-center' : 'px-3 justify-between'"
    >
      <div class="flex items-center min-w-0 transition-all duration-300 ease-in-out" :class="sidebarCollapsed ? 'lg:gap-0' : 'gap-3'">
        <i class="ph-bold ph-currency-circle-dollar text-lg shrink-0 {{ $isAr ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300' }}"></i>
        <span 
          class="sidebar-label whitespace-nowrap overflow-hidden transition-all duration-300 ease-in-out"
          :class="sidebarCollapsed ? 'lg:max-w-0 lg:opacity-0 lg:-translate-x-2' : 'max-w-[160px] opacity-100 translate-x-0'"
        >Accounts Receivable</span>
      </div>
      @if($isAr)
        <span 
          class="sidebar-indicator h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400 shrink-0 transition-all duration-300 ease-in-out"
          :class="sidebarCollapsed ? 'lg:opacity-0 lg:scale-0' : 'opacity-100 scale-100'"
        ></span>
      @endif

      <!-- Floating Tooltip on Hover (Desktop Collapsed Mode) -->
      <div 
        x-show="sidebarCollapsed" 
        x-cloak 
        class="hidden lg:group-hover:flex absolute left-full ml-3 top-1/2 -translate-y-1/2 z-50 items-center px-2.5 py-1.5 rounded-xl bg-slate-900 text-white text-xs font-semibold shadow-xl whitespace-nowrap pointer-events-none ring-1 ring-slate-800 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
      >
        <span>Accounts Receivable</span>
        <span class="absolute right-full top-1/2 -translate-y-1/2 border-4 border-transparent border-r-slate-900 dark:border-r-slate-800"></span>
      </div>
    </a>
    @endcan

    <!-- 3. Disbursement Management -->
    @can('access-disbursements')
    <a 
      href="{{ route('disbursement.payment-requests') }}" 
      class="sidebar-link group relative flex items-center rounded-xl text-sm font-medium transition-colors duration-150 py-2.5 overflow-hidden {{ $isDisbursement ? 'bg-emerald-50 text-emerald-800 font-semibold ring-1 ring-emerald-600/20 dark:bg-emerald-950/50 dark:text-emerald-300 dark:ring-emerald-500/30' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/70 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800/50' }}"
      :class="sidebarCollapsed ? 'lg:px-0 lg:justify-center' : 'px-3 justify-between'"
    >
      <div class="flex items-center min-w-0 transition-all duration-300 ease-in-out" :class="sidebarCollapsed ? 'lg:gap-0' : 'gap-3'">
        <i class="ph-bold ph-arrows-out text-lg shrink-0 {{ $isDisbursement ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300' }}"></i>
        <span 
          class="sidebar-label whitespace-nowrap overflow-hidden transition-all duration-300 ease-in-out"
          :class="sidebarCollapsed ? 'lg:max-w-0 lg:opacity-0 lg:-translate-x-2' : 'max-w-[160px] opacity-100 translate-x-0'"
        >Disbursement Management</span>
      </div>
      @if($isDisbursement)
        <span 
          class="sidebar-indicator h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400 shrink-0 transition-all duration-300 ease-in-out"
          :class="sidebarCollapsed ? 'lg:opacity-0 lg:scale-0' : 'opacity-100 scale-100'"
        ></span>
      @endif

      <!-- Floating Tooltip on Hover (Desktop Collapsed Mode) -->
      <div 
        x-show="sidebarCollapsed" 
        x-cloak 
        class="hidden lg:group-hover:flex absolute left-full ml-3 top-1/2 -translate-y-1/2 z-50 items-center px-2.5 py-1.5 rounded-xl bg-slate-900 text-white text-xs font-semibold shadow-xl whitespace-nowrap pointer-events-none ring-1 ring-slate-800 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
      >
        <span>Disbursement Management</span>
        <span class="absolute right-full top-1/2 -translate-y-1/2 border-4 border-transparent border-r-slate-900 dark:border-r-slate-800"></span>
      </div>
    </a>
    @endcan

    <!-- 4. Collection Management -->
    @can('access-cashier-pos')
    <a 
      href="{{ route('collection.cashier-desk') }}" 
      class="sidebar-link group relative flex items-center rounded-xl text-sm font-medium transition-colors duration-150 py-2.5 overflow-hidden {{ $isCollection ? 'bg-emerald-50 text-emerald-800 font-semibold ring-1 ring-emerald-600/20 dark:bg-emerald-950/50 dark:text-emerald-300 dark:ring-emerald-500/30' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/70 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800/50' }}"
      :class="sidebarCollapsed ? 'lg:px-0 lg:justify-center' : 'px-3 justify-between'"
    >
      <div class="flex items-center min-w-0 transition-all duration-300 ease-in-out" :class="sidebarCollapsed ? 'lg:gap-0' : 'gap-3'">
        <i class="ph-bold ph-hand-coins text-lg shrink-0 {{ $isCollection ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300' }}"></i>
        <span 
          class="sidebar-label whitespace-nowrap overflow-hidden transition-all duration-300 ease-in-out"
          :class="sidebarCollapsed ? 'lg:max-w-0 lg:opacity-0 lg:-translate-x-2' : 'max-w-[160px] opacity-100 translate-x-0'"
        >Collection Management</span>
      </div>
      @if($isCollection)
        <span 
          class="sidebar-indicator h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400 shrink-0 transition-all duration-300 ease-in-out"
          :class="sidebarCollapsed ? 'lg:opacity-0 lg:scale-0' : 'opacity-100 scale-100'"
        ></span>
      @endif

      <!-- Floating Tooltip on Hover (Desktop Collapsed Mode) -->
      <div 
        x-show="sidebarCollapsed" 
        x-cloak 
        class="hidden lg:group-hover:flex absolute left-full ml-3 top-1/2 -translate-y-1/2 z-50 items-center px-2.5 py-1.5 rounded-xl bg-slate-900 text-white text-xs font-semibold shadow-xl whitespace-nowrap pointer-events-none ring-1 ring-slate-800 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
      >
        <span>Collection Management</span>
        <span class="absolute right-full top-1/2 -translate-y-1/2 border-4 border-transparent border-r-slate-900 dark:border-r-slate-800"></span>
      </div>
    </a>
    @endcan

    <!-- 5. Budget Management -->
    @can('access-budget')
    <a 
      href="{{ route('budget.fiscal-planning') }}" 
      class="sidebar-link group relative flex items-center rounded-xl text-sm font-medium transition-colors duration-150 py-2.5 overflow-hidden {{ $isBudget ? 'bg-emerald-50 text-emerald-800 font-semibold ring-1 ring-emerald-600/20 dark:bg-emerald-950/50 dark:text-emerald-300 dark:ring-emerald-500/30' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/70 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800/50' }}"
      :class="sidebarCollapsed ? 'lg:px-0 lg:justify-center' : 'px-3 justify-between'"
    >
      <div class="flex items-center min-w-0 transition-all duration-300 ease-in-out" :class="sidebarCollapsed ? 'lg:gap-0' : 'gap-3'">
        <i class="ph-bold ph-calculator text-lg shrink-0 {{ $isBudget ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300' }}"></i>
        <span 
          class="sidebar-label whitespace-nowrap overflow-hidden transition-all duration-300 ease-in-out"
          :class="sidebarCollapsed ? 'lg:max-w-0 lg:opacity-0 lg:-translate-x-2' : 'max-w-[160px] opacity-100 translate-x-0'"
        >Budget Management</span>
      </div>
      @if($isBudget)
        <span 
          class="sidebar-indicator h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400 shrink-0 transition-all duration-300 ease-in-out"
          :class="sidebarCollapsed ? 'lg:opacity-0 lg:scale-0' : 'opacity-100 scale-100'"
        ></span>
      @endif

      <!-- Floating Tooltip on Hover (Desktop Collapsed Mode) -->
      <div 
        x-show="sidebarCollapsed" 
        x-cloak 
        class="hidden lg:group-hover:flex absolute left-full ml-3 top-1/2 -translate-y-1/2 z-50 items-center px-2.5 py-1.5 rounded-xl bg-slate-900 text-white text-xs font-semibold shadow-xl whitespace-nowrap pointer-events-none ring-1 ring-slate-800 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
      >
        <span>Budget Management</span>
        <span class="absolute right-full top-1/2 -translate-y-1/2 border-4 border-transparent border-r-slate-900 dark:border-r-slate-800"></span>
      </div>
    </a>
    @endcan

    <!-- 6. General Ledger (GL) -->
    @can('access-general-ledger')
    <a 
      href="{{ route('gl.journal-entries') }}" 
      class="sidebar-link group relative flex items-center rounded-xl text-sm font-medium transition-colors duration-150 py-2.5 overflow-hidden {{ $isGl ? 'bg-emerald-50 text-emerald-800 font-semibold ring-1 ring-emerald-600/20 dark:bg-emerald-950/50 dark:text-emerald-300 dark:ring-emerald-500/30' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/70 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800/50' }}"
      :class="sidebarCollapsed ? 'lg:px-0 lg:justify-center' : 'px-3 justify-between'"
    >
      <div class="flex items-center min-w-0 transition-all duration-300 ease-in-out" :class="sidebarCollapsed ? 'lg:gap-0' : 'gap-3'">
        <i class="ph-bold ph-book-open-text text-lg shrink-0 {{ $isGl ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300' }}"></i>
        <span 
          class="sidebar-label whitespace-nowrap overflow-hidden transition-all duration-300 ease-in-out"
          :class="sidebarCollapsed ? 'lg:max-w-0 lg:opacity-0 lg:-translate-x-2' : 'max-w-[160px] opacity-100 translate-x-0'"
        >General Ledger</span>
      </div>
      @if($isGl)
        <span 
          class="sidebar-indicator h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400 shrink-0 transition-all duration-300 ease-in-out"
          :class="sidebarCollapsed ? 'lg:opacity-0 lg:scale-0' : 'opacity-100 scale-100'"
        ></span>
      @endif

      <!-- Floating Tooltip on Hover (Desktop Collapsed Mode) -->
      <div 
        x-show="sidebarCollapsed" 
        x-cloak 
        class="hidden lg:group-hover:flex absolute left-full ml-3 top-1/2 -translate-y-1/2 z-50 items-center px-2.5 py-1.5 rounded-xl bg-slate-900 text-white text-xs font-semibold shadow-xl whitespace-nowrap pointer-events-none ring-1 ring-slate-800 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
      >
        <span>General Ledger</span>
        <span class="absolute right-full top-1/2 -translate-y-1/2 border-4 border-transparent border-r-slate-900 dark:border-r-slate-800"></span>
      </div>
    </a>
    @endcan

    <!-- 7. User & Security -->
    @can('access-user-management')
    <a 
      href="{{ route('user-security.users') }}" 
      class="sidebar-link group relative flex items-center rounded-xl text-sm font-medium transition-colors duration-150 py-2.5 overflow-hidden {{ $isUserSecurity ? 'bg-emerald-50 text-emerald-800 font-semibold ring-1 ring-emerald-600/20 dark:bg-emerald-950/50 dark:text-emerald-300 dark:ring-emerald-500/30' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/70 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800/50' }}"
      :class="sidebarCollapsed ? 'lg:px-0 lg:justify-center' : 'px-3 justify-between'"
    >
      <div class="flex items-center min-w-0 transition-all duration-300 ease-in-out" :class="sidebarCollapsed ? 'lg:gap-0' : 'gap-3'">
        <i class="ph-bold ph-shield-check text-lg shrink-0 {{ $isUserSecurity ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300' }}"></i>
        <span 
          class="sidebar-label whitespace-nowrap overflow-hidden transition-all duration-300 ease-in-out"
          :class="sidebarCollapsed ? 'lg:max-w-0 lg:opacity-0 lg:-translate-x-2' : 'max-w-[160px] opacity-100 translate-x-0'"
        >User &amp; Security</span>
      </div>
      <div 
        class="flex items-center gap-1.5 transition-all duration-300 ease-in-out"
        :class="sidebarCollapsed ? 'lg:max-w-0 lg:opacity-0 lg:scale-0' : 'max-w-[60px] opacity-100 scale-100'"
      >
        @if(($pendingWorkstationsCount ?? 0) > 0)
          <span class="rounded-md bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700 ring-1 ring-amber-600/20 dark:bg-amber-950/60 dark:text-amber-300">{{ $pendingWorkstationsCount }}</span>
        @endif
        @if($isUserSecurity)
          <span class="sidebar-indicator h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400 shrink-0"></span>
        @endif
      </div>

      @if(($pendingWorkstationsCount ?? 0) > 0)
        <!-- Collapsed Amber Notification Indicator Dot -->
        <span 
          x-show="sidebarCollapsed" 
          x-cloak 
          class="hidden lg:block absolute top-1.5 right-1.5 h-2 w-2 rounded-full bg-amber-500 ring-2 ring-white dark:ring-slate-900"
          title="{{ $pendingWorkstationsCount }} pending workstation approval{{ $pendingWorkstationsCount > 1 ? 's' : '' }}"
        ></span>
      @endif

      <!-- Floating Tooltip on Hover (Desktop Collapsed Mode) -->
      <div 
        x-show="sidebarCollapsed" 
        x-cloak 
        class="hidden lg:group-hover:flex absolute left-full ml-3 top-1/2 -translate-y-1/2 z-50 items-center gap-2 px-2.5 py-1.5 rounded-xl bg-slate-900 text-white text-xs font-semibold shadow-xl whitespace-nowrap pointer-events-none ring-1 ring-slate-800 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
      >
        <span>User &amp; Security</span>
        @if(($pendingWorkstationsCount ?? 0) > 0)
          <span class="rounded-md bg-amber-500 px-1.5 py-0.2 text-[10px] font-bold text-white">{{ $pendingWorkstationsCount }}</span>
        @endif
        <span class="absolute right-full top-1/2 -translate-y-1/2 border-4 border-transparent border-r-slate-900 dark:border-r-slate-800"></span>
      </div>
    </a>
    @endcan
  </nav>

  <!-- Sidebar Footer: System Status & Version Badge -->
  <div 
    class="p-3 border-t border-slate-200/80 bg-slate-50/60 dark:border-slate-800 dark:bg-slate-900/60 transition-all duration-300 ease-in-out overflow-hidden"
    :class="sidebarCollapsed ? 'lg:p-2 lg:flex lg:justify-center' : ''"
  >
    <div 
      class="flex items-center justify-between px-2 py-1 text-[11px] text-slate-500 dark:text-slate-400 w-full transition-all duration-300 ease-in-out"
      :class="sidebarCollapsed ? 'lg:px-0 lg:justify-center' : ''"
      :title="sidebarCollapsed ? 'Terminal Online • v2.4 CAS' : ''"
    >
      <div class="flex items-center gap-2" :class="sidebarCollapsed ? 'lg:gap-0' : ''">
        <span class="relative flex h-2 w-2 shrink-0">
          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
          <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
        </span>
        <span 
          class="font-medium text-slate-600 dark:text-slate-300 whitespace-nowrap overflow-hidden transition-all duration-300 ease-in-out"
          :class="sidebarCollapsed ? 'lg:max-w-0 lg:opacity-0' : 'max-w-[120px] opacity-100'"
        >
          Terminal Online
        </span>
      </div>
      <span 
        class="font-mono text-[10px] text-slate-400 whitespace-nowrap overflow-hidden transition-all duration-300 ease-in-out"
        :class="sidebarCollapsed ? 'lg:max-w-0 lg:opacity-0' : 'max-w-[80px] opacity-100'"
      >
        v2.4 CAS
      </span>
    </div>
  </div>
</aside>

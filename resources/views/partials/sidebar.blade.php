@php
  $isDashboard = request()->routeIs('accounting.dashboard') || request()->routeIs('dashboard');
  $isGl = request()->routeIs('gl.*') || request()->routeIs('accounting.general-ledger.*') || request()->routeIs('accounting.period-close.*');
  $isAp = request()->routeIs('ap.*');
  $isAr = request()->routeIs('ar.*');
  $isDisbursement = request()->routeIs('disbursement.*');
  $isCollection = request()->routeIs('collection.*') || request()->routeIs('accounting.cashier.*');
  $isBudget = request()->routeIs('budget.*');
  $isUserSecurity = request()->routeIs('user-security.*') || request()->routeIs('accounting.audit-log');

  $initialActiveModule = null;
  if ($isAp) {
      $initialActiveModule = 'ap';
  } elseif ($isAr) {
      $initialActiveModule = 'ar';
  } elseif ($isDisbursement) {
      $initialActiveModule = 'disbursement';
  } elseif ($isCollection) {
      $initialActiveModule = 'collection';
  } elseif ($isBudget) {
      $initialActiveModule = 'budget';
  } elseif ($isGl) {
      $initialActiveModule = 'gl';
  } elseif ($isUserSecurity) {
      $initialActiveModule = 'userSecurity';
  }
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

  <!-- Navigation Links with Smooth Collapse Accordion Dropdowns -->
  <nav 
    x-data="{
      activeModule: @js($initialActiveModule),
      toggleModule(moduleKey) {
        if (sidebarCollapsed) {
          toggleSidebarCollapse();
          this.activeModule = moduleKey;
          return;
        }
        // Exclusive accordion: clicking another module opens it and closes the previous one smoothly.
        // Clicking the currently open module closes it.
        this.activeModule = (this.activeModule === moduleKey) ? null : moduleKey;
      }
    }"
    class="flex-1 overflow-y-auto py-3 space-y-1 custom-scrollbar transition-all duration-300 ease-in-out" 
    :class="sidebarCollapsed ? 'lg:px-2 px-3.5' : 'px-3.5'"
    aria-label="Main menu"
  >
    
    <!-- 0. Dashboard (Direct Link) -->
    @can('access-general-ledger')
    <div class="sidebar-module-group">
      <a 
        href="{{ route('accounting.dashboard') }}" 
        class="sidebar-link group relative flex items-center rounded-xl text-sm font-medium transition-colors duration-150 py-2.5 overflow-hidden {{ $isDashboard ? 'bg-emerald-50 text-emerald-900 font-semibold ring-1 ring-emerald-600/20 dark:bg-emerald-950/50 dark:text-emerald-300 dark:ring-emerald-500/30' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800/60' }}"
        :class="sidebarCollapsed ? 'lg:px-0 lg:justify-center' : 'px-3 justify-between'"
      >
        <div class="flex items-center min-w-0 transition-all duration-300 ease-in-out" :class="sidebarCollapsed ? 'lg:gap-0' : 'gap-3'">
          <i class="ph-bold ph-squares-four text-lg shrink-0 {{ $isDashboard ? 'text-emerald-700 dark:text-emerald-400' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300' }}"></i>
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
    </div>
    @endcan

    <!-- 1. Accounts Payable (AP Dropdown) -->
    @can('access-ap-procurement')
    <div class="sidebar-module-group">
      <button 
        type="button" 
        @click="toggleModule('ap')"
        class="sidebar-link w-full group relative flex items-center rounded-xl text-sm transition-all duration-200 ease-in-out py-2.5 overflow-hidden select-none"
        :class="[
          sidebarCollapsed ? 'lg:px-0 lg:justify-center' : 'px-3 justify-between',
          activeModule === 'ap' 
            ? 'bg-emerald-50 text-emerald-900 font-semibold ring-1 ring-emerald-600/20 dark:bg-emerald-950/60 dark:text-emerald-300 dark:ring-emerald-500/30' 
            : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/70 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800/50 font-medium'
        ]"
        :title="sidebarCollapsed ? 'Accounts Payable' : ''"
      >
        <div class="flex items-center min-w-0 transition-all duration-300 ease-in-out" :class="sidebarCollapsed ? 'lg:gap-0' : 'gap-3'">
          <i 
            class="ph-bold ph-receipt text-lg shrink-0 transition-colors duration-200"
            :class="activeModule === 'ap' 
              ? 'text-emerald-700 dark:text-emerald-400' 
              : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300'"
          ></i>
          <span 
            class="sidebar-label whitespace-nowrap overflow-hidden transition-all duration-300 ease-in-out text-left"
            :class="[
              sidebarCollapsed ? 'lg:max-w-0 lg:opacity-0 lg:-translate-x-2' : 'max-w-[150px] opacity-100 translate-x-0',
              activeModule === 'ap' ? 'text-emerald-900 dark:text-emerald-200' : 'text-slate-700 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white'
            ]"
          >Accounts Payable</span>
        </div>

        <!-- Caret Indicator -->
        <div 
          class="sidebar-caret flex items-center transition-all duration-300 ease-in-out"
          :class="sidebarCollapsed ? 'lg:hidden' : 'opacity-100'"
        >
          <i 
            class="ph-bold ph-caret-down text-xs shrink-0 transition-transform duration-250 ease-in-out"
            :class="activeModule === 'ap' 
              ? 'rotate-180 text-emerald-700 dark:text-emerald-400' 
              : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300'"
          ></i>
        </div>

        <!-- Tooltip in Collapsed Mode -->
        <div 
          x-show="sidebarCollapsed" 
          x-cloak 
          class="hidden lg:group-hover:flex absolute left-full ml-3 top-1/2 -translate-y-1/2 z-50 items-center px-2.5 py-1.5 rounded-xl bg-slate-900 text-white text-xs font-semibold shadow-xl whitespace-nowrap pointer-events-none ring-1 ring-slate-800 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
        >
          <span>Accounts Payable</span>
          <span class="absolute right-full top-1/2 -translate-y-1/2 border-4 border-transparent border-r-slate-900 dark:border-r-slate-800"></span>
        </div>
      </button>

      <!-- AP Sub-Menu Items (Smooth Collapse Animation) -->
      <div 
        x-show="activeModule === 'ap' && !sidebarCollapsed" 
        x-collapse.duration.250ms
        x-cloak
        class="sidebar-submenu ml-3.5 pl-3 border-l-2 border-slate-100 dark:border-slate-800 space-y-0.5 pt-1 pb-0.5"
      >
        <a 
          href="{{ route('ap.vendors') }}" 
          class="group flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors duration-150 {{ request()->routeIs('ap.vendors*') ? 'bg-emerald-50 text-emerald-800 font-semibold dark:bg-emerald-950/60 dark:text-emerald-300' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800/60' }}"
        >
          <div class="flex items-center gap-2 min-w-0">
            <i class="ph-bold ph-address-book text-sm shrink-0 {{ request()->routeIs('ap.vendors*') ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300' }}"></i>
            <span class="truncate">Vendor Directory</span>
          </div>
          @if(request()->routeIs('ap.vendors*'))
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400 shrink-0"></span>
          @endif
        </a>

        <a 
          href="{{ route('ap.invoices') }}" 
          class="group flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors duration-150 {{ request()->routeIs('ap.invoices*') ? 'bg-emerald-50 text-emerald-800 font-semibold dark:bg-emerald-950/60 dark:text-emerald-300' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800/60' }}"
        >
          <div class="flex items-center gap-2 min-w-0">
            <i class="ph-bold ph-receipt text-sm shrink-0 {{ request()->routeIs('ap.invoices*') ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300' }}"></i>
            <span class="truncate">Vendor Invoices &amp; Tax</span>
          </div>
          @if(request()->routeIs('ap.invoices*'))
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400 shrink-0"></span>
          @endif
        </a>

        <a 
          href="{{ route('ap.purchase-bills') }}" 
          class="group flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors duration-150 {{ request()->routeIs('ap.purchase-bills*') ? 'bg-emerald-50 text-emerald-800 font-semibold dark:bg-emerald-950/60 dark:text-emerald-300' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800/60' }}"
        >
          <div class="flex items-center gap-2 min-w-0">
            <i class="ph-bold ph-file-search text-sm shrink-0 {{ request()->routeIs('ap.purchase-bills*') ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300' }}"></i>
            <span class="truncate">3-Way Verification / Bills</span>
          </div>
          @if(request()->routeIs('ap.purchase-bills*'))
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400 shrink-0"></span>
          @endif
        </a>

        <a 
          href="{{ route('ap.payable-aging') }}" 
          class="group flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors duration-150 {{ request()->routeIs('ap.payable-aging*') ? 'bg-emerald-50 text-emerald-800 font-semibold dark:bg-emerald-950/60 dark:text-emerald-300' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800/60' }}"
        >
          <div class="flex items-center gap-2 min-w-0">
            <i class="ph-bold ph-hourglass text-sm shrink-0 {{ request()->routeIs('ap.payable-aging*') ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300' }}"></i>
            <span class="truncate">Payable Aging</span>
          </div>
          @if(request()->routeIs('ap.payable-aging*'))
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400 shrink-0"></span>
          @endif
        </a>

        <a 
          href="{{ route('ap.ap-approvals') }}" 
          class="group flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors duration-150 {{ (request()->routeIs('ap.ap-approvals*') || request()->routeIs('ap.payment-approvals.*') || request()->routeIs('ap.approvals*')) ? 'bg-emerald-50 text-emerald-800 font-semibold dark:bg-emerald-950/60 dark:text-emerald-300' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800/60' }}"
        >
          <div class="flex items-center gap-2 min-w-0">
            <i class="ph-bold ph-check-circle text-sm shrink-0 {{ (request()->routeIs('ap.ap-approvals*') || request()->routeIs('ap.payment-approvals.*') || request()->routeIs('ap.approvals*')) ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300' }}"></i>
            <span class="truncate">Payment Approvals</span>
          </div>
          @if(request()->routeIs('ap.ap-approvals*') || request()->routeIs('ap.payment-approvals.*') || request()->routeIs('ap.approvals*'))
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400 shrink-0"></span>
          @endif
        </a>
      </div>
    </div>
    @endcan

    <!-- 2. Accounts Receivable (AR Dropdown) -->
    @can('access-ar-billing')
    <div class="sidebar-module-group">
      <button 
        type="button" 
        @click="toggleModule('ar')"
        class="sidebar-link w-full group relative flex items-center rounded-xl text-sm transition-all duration-200 ease-in-out py-2.5 overflow-hidden select-none"
        :class="[
          sidebarCollapsed ? 'lg:px-0 lg:justify-center' : 'px-3 justify-between',
          activeModule === 'ar' 
            ? 'bg-emerald-50 text-emerald-900 font-semibold ring-1 ring-emerald-600/20 dark:bg-emerald-950/60 dark:text-emerald-300 dark:ring-emerald-500/30' 
            : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/70 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800/50 font-medium'
        ]"
        :title="sidebarCollapsed ? 'Accounts Receivable' : ''"
      >
        <div class="flex items-center min-w-0 transition-all duration-300 ease-in-out" :class="sidebarCollapsed ? 'lg:gap-0' : 'gap-3'">
          <i 
            class="ph-bold ph-currency-circle-dollar text-lg shrink-0 transition-colors duration-200"
            :class="activeModule === 'ar' 
              ? 'text-emerald-700 dark:text-emerald-400' 
              : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300'"
          ></i>
          <span 
            class="sidebar-label whitespace-nowrap overflow-hidden transition-all duration-300 ease-in-out text-left"
            :class="[
              sidebarCollapsed ? 'lg:max-w-0 lg:opacity-0 lg:-translate-x-2' : 'max-w-[150px] opacity-100 translate-x-0',
              activeModule === 'ar' ? 'text-emerald-900 dark:text-emerald-200' : 'text-slate-700 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white'
            ]"
          >Accounts Receivable</span>
        </div>

        <!-- Caret Indicator -->
        <div 
          class="sidebar-caret flex items-center transition-all duration-300 ease-in-out"
          :class="sidebarCollapsed ? 'lg:hidden' : 'opacity-100'"
        >
          <i 
            class="ph-bold ph-caret-down text-xs shrink-0 transition-transform duration-250 ease-in-out"
            :class="activeModule === 'ar' 
              ? 'rotate-180 text-emerald-700 dark:text-emerald-400' 
              : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300'"
          ></i>
        </div>

        <!-- Tooltip in Collapsed Mode -->
        <div 
          x-show="sidebarCollapsed" 
          x-cloak 
          class="hidden lg:group-hover:flex absolute left-full ml-3 top-1/2 -translate-y-1/2 z-50 items-center px-2.5 py-1.5 rounded-xl bg-slate-900 text-white text-xs font-semibold shadow-xl whitespace-nowrap pointer-events-none ring-1 ring-slate-800 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
        >
          <span>Accounts Receivable</span>
          <span class="absolute right-full top-1/2 -translate-y-1/2 border-4 border-transparent border-r-slate-900 dark:border-r-slate-800"></span>
        </div>
      </button>

      <!-- AR Sub-Menu Items (Smooth Collapse Animation) -->
      <div 
        x-show="activeModule === 'ar' && !sidebarCollapsed" 
        x-collapse.duration.250ms
        x-cloak
        class="sidebar-submenu ml-3.5 pl-3 border-l-2 border-slate-100 dark:border-slate-800 space-y-0.5 pt-1 pb-0.5"
      >
        <a 
          href="{{ route('ar.billing') }}" 
          class="group flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors duration-150 {{ (request()->routeIs('ar.billing*') || request()->routeIs('ar.invoices.*')) ? 'bg-emerald-50 text-emerald-800 font-semibold dark:bg-emerald-950/60 dark:text-emerald-300' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800/60' }}"
        >
          <div class="flex items-center gap-2 min-w-0">
            <i class="ph-bold ph-file-text text-sm shrink-0 {{ (request()->routeIs('ar.billing*') || request()->routeIs('ar.invoices.*')) ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300' }}"></i>
            <span class="truncate">Invoicing &amp; Patient Billing</span>
          </div>
          @if(request()->routeIs('ar.billing*'))
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400 shrink-0"></span>
          @endif
        </a>

        <a 
          href="{{ route('ar.customers') }}" 
          class="group flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors duration-150 {{ (request()->routeIs('ar.customers*') || request()->routeIs('ar.patients.*')) ? 'bg-emerald-50 text-emerald-800 font-semibold dark:bg-emerald-950/60 dark:text-emerald-300' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800/60' }}"
        >
          <div class="flex items-center gap-2 min-w-0">
            <i class="ph-bold ph-users text-sm shrink-0 {{ (request()->routeIs('ar.customers*') || request()->routeIs('ar.patients.*')) ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300' }}"></i>
            <span class="truncate">Patient Accounts</span>
          </div>
          @if(request()->routeIs('ar.customers*'))
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400 shrink-0"></span>
          @endif
        </a>

        <a 
          href="{{ route('ar.credit-notes') }}" 
          class="group flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors duration-150 {{ request()->routeIs('ar.credit-notes*') ? 'bg-emerald-50 text-emerald-800 font-semibold dark:bg-emerald-950/60 dark:text-emerald-300' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800/60' }}"
        >
          <div class="flex items-center gap-2 min-w-0">
            <i class="ph-bold ph-arrows-clockwise text-sm shrink-0 {{ request()->routeIs('ar.credit-notes*') ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300' }}"></i>
            <span class="truncate">Credit Notes &amp; Adjustments</span>
          </div>
          @if(request()->routeIs('ar.credit-notes*'))
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400 shrink-0"></span>
          @endif
        </a>

        <a 
          href="{{ route('ar.malasakit.index') }}" 
          class="group flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors duration-150 {{ request()->routeIs('ar.malasakit.*') ? 'bg-emerald-50 text-emerald-800 font-semibold dark:bg-emerald-950/60 dark:text-emerald-300' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800/60' }}"
        >
          <div class="flex items-center gap-2 min-w-0">
            <i class="ph-bold ph-heart-straight text-sm shrink-0 {{ request()->routeIs('ar.malasakit.*') ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300' }}"></i>
            <span class="truncate">Malasakit Subsidies (RA 11463)</span>
          </div>
          @if(request()->routeIs('ar.malasakit.*'))
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400 shrink-0"></span>
          @endif
        </a>

        <a 
          href="{{ route('ar.ar-aging') }}" 
          class="group flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors duration-150 {{ request()->routeIs('ar.ar-aging*') ? 'bg-emerald-50 text-emerald-800 font-semibold dark:bg-emerald-950/60 dark:text-emerald-300' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800/60' }}"
        >
          <div class="flex items-center gap-2 min-w-0">
            <i class="ph-bold ph-clock-countdown text-sm shrink-0 {{ request()->routeIs('ar.ar-aging*') ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300' }}"></i>
            <span class="truncate">Receivable Aging Schedule</span>
          </div>
          @if(request()->routeIs('ar.ar-aging*'))
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400 shrink-0"></span>
          @endif
        </a>

        <a 
          href="{{ route('ar.statements') }}" 
          class="group flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors duration-150 {{ request()->routeIs('ar.statements*') ? 'bg-emerald-50 text-emerald-800 font-semibold dark:bg-emerald-950/60 dark:text-emerald-300' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800/60' }}"
        >
          <div class="flex items-center gap-2 min-w-0">
            <i class="ph-bold ph-scroll text-sm shrink-0 {{ request()->routeIs('ar.statements*') ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300' }}"></i>
            <span class="truncate">Statements of Account (SOA)</span>
          </div>
          @if(request()->routeIs('ar.statements*'))
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400 shrink-0"></span>
          @endif
        </a>
      </div>
    </div>
    @endcan

    <!-- 3. Disbursement Management (Dropdown) -->
    @can('access-disbursements')
    <div class="sidebar-module-group">
      <button 
        type="button" 
        @click="toggleModule('disbursement')"
        class="sidebar-link w-full group relative flex items-center rounded-xl text-sm transition-all duration-200 ease-in-out py-2.5 overflow-hidden select-none"
        :class="[
          sidebarCollapsed ? 'lg:px-0 lg:justify-center' : 'px-3 justify-between',
          activeModule === 'disbursement' 
            ? 'bg-emerald-50 text-emerald-900 font-semibold ring-1 ring-emerald-600/20 dark:bg-emerald-950/60 dark:text-emerald-300 dark:ring-emerald-500/30' 
            : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/70 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800/50 font-medium'
        ]"
        :title="sidebarCollapsed ? 'Disbursement Management' : ''"
      >
        <div class="flex items-center min-w-0 transition-all duration-300 ease-in-out" :class="sidebarCollapsed ? 'lg:gap-0' : 'gap-3'">
          <i 
            class="ph-bold ph-arrows-out text-lg shrink-0 transition-colors duration-200"
            :class="activeModule === 'disbursement' 
              ? 'text-emerald-700 dark:text-emerald-400' 
              : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300'"
          ></i>
          <span 
            class="sidebar-label whitespace-nowrap overflow-hidden transition-all duration-300 ease-in-out text-left"
            :class="[
              sidebarCollapsed ? 'lg:max-w-0 lg:opacity-0 lg:-translate-x-2' : 'max-w-[150px] opacity-100 translate-x-0',
              activeModule === 'disbursement' ? 'text-emerald-900 dark:text-emerald-200' : 'text-slate-700 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white'
            ]"
          >Disbursement</span>
        </div>

        <!-- Caret Indicator -->
        <div 
          class="sidebar-caret flex items-center transition-all duration-300 ease-in-out"
          :class="sidebarCollapsed ? 'lg:hidden' : 'opacity-100'"
        >
          <i 
            class="ph-bold ph-caret-down text-xs shrink-0 transition-transform duration-250 ease-in-out"
            :class="activeModule === 'disbursement' 
              ? 'rotate-180 text-emerald-700 dark:text-emerald-400' 
              : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300'"
          ></i>
        </div>

        <!-- Tooltip in Collapsed Mode -->
        <div 
          x-show="sidebarCollapsed" 
          x-cloak 
          class="hidden lg:group-hover:flex absolute left-full ml-3 top-1/2 -translate-y-1/2 z-50 items-center px-2.5 py-1.5 rounded-xl bg-slate-900 text-white text-xs font-semibold shadow-xl whitespace-nowrap pointer-events-none ring-1 ring-slate-800 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
        >
          <span>Disbursement Management</span>
          <span class="absolute right-full top-1/2 -translate-y-1/2 border-4 border-transparent border-r-slate-900 dark:border-r-slate-800"></span>
        </div>
      </button>

      <!-- Disbursement Sub-Menu Items (Smooth Collapse Animation) -->
      <div 
        x-show="activeModule === 'disbursement' && !sidebarCollapsed" 
        x-collapse.duration.250ms
        x-cloak
        class="sidebar-submenu ml-3.5 pl-3 border-l-2 border-slate-100 dark:border-slate-800 space-y-0.5 pt-1 pb-0.5"
      >
        <a 
          href="{{ route('disbursement.payment-requests') }}" 
          class="group flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors duration-150 {{ request()->routeIs('disbursement.payment-requests*') ? 'bg-emerald-50 text-emerald-800 font-semibold dark:bg-emerald-950/60 dark:text-emerald-300' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800/60' }}"
        >
          <div class="flex items-center gap-2 min-w-0">
            <i class="ph-bold ph-receipt text-sm shrink-0 {{ request()->routeIs('disbursement.payment-requests*') ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300' }}"></i>
            <span class="truncate">Payment Vouchers (DV)</span>
          </div>
          @if(request()->routeIs('disbursement.payment-requests*'))
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400 shrink-0"></span>
          @endif
        </a>

        <a 
          href="{{ route('disbursement.disbursement-approval') }}" 
          class="group flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors duration-150 {{ request()->routeIs('disbursement.disbursement-approval*') ? 'bg-emerald-50 text-emerald-800 font-semibold dark:bg-emerald-950/60 dark:text-emerald-300' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800/60' }}"
        >
          <div class="flex items-center gap-2 min-w-0">
            <i class="ph-bold ph-seal-check text-sm shrink-0 {{ request()->routeIs('disbursement.disbursement-approval*') ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300' }}"></i>
            <span class="truncate">Voucher Approvals &amp; Release</span>
          </div>
          @if(request()->routeIs('disbursement.disbursement-approval*'))
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400 shrink-0"></span>
          @endif
        </a>

        <a 
          href="{{ route('disbursement.check-register') }}" 
          class="group flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors duration-150 {{ request()->routeIs('disbursement.check-register*') ? 'bg-emerald-50 text-emerald-800 font-semibold dark:bg-emerald-950/60 dark:text-emerald-300' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800/60' }}"
        >
          <div class="flex items-center gap-2 min-w-0">
            <i class="ph-bold ph-money text-sm shrink-0 {{ request()->routeIs('disbursement.check-register*') ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300' }}"></i>
            <span class="truncate">Check Register &amp; Custody</span>
          </div>
          @if(request()->routeIs('disbursement.check-register*'))
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400 shrink-0"></span>
          @endif
        </a>

        <a 
          href="{{ route('disbursement.eft-transfers') }}" 
          class="group flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors duration-150 {{ request()->routeIs('disbursement.eft-transfers*') ? 'bg-emerald-50 text-emerald-800 font-semibold dark:bg-emerald-950/60 dark:text-emerald-300' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800/60' }}"
        >
          <div class="flex items-center gap-2 min-w-0">
            <i class="ph-bold ph-arrows-left-right text-sm shrink-0 {{ request()->routeIs('disbursement.eft-transfers*') ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300' }}"></i>
            <span class="truncate">EFT Bank Transfers</span>
          </div>
          @if(request()->routeIs('disbursement.eft-transfers*'))
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400 shrink-0"></span>
          @endif
        </a>

        <a 
          href="{{ route('disbursement.petty-cash') }}" 
          class="group flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors duration-150 {{ request()->routeIs('disbursement.petty-cash*') ? 'bg-emerald-50 text-emerald-800 font-semibold dark:bg-emerald-950/60 dark:text-emerald-300' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800/60' }}"
        >
          <div class="flex items-center gap-2 min-w-0">
            <i class="ph-bold ph-vault text-sm shrink-0 {{ request()->routeIs('disbursement.petty-cash*') ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300' }}"></i>
            <span class="truncate">Petty Cash Custody Funds</span>
          </div>
          @if(request()->routeIs('disbursement.petty-cash*'))
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400 shrink-0"></span>
          @endif
        </a>
      </div>
    </div>
    @endcan

    <!-- 4. Collection Management (Dropdown) -->
    @can('access-cashier-pos')
    <div class="sidebar-module-group">
      <button 
        type="button" 
        @click="toggleModule('collection')"
        class="sidebar-link w-full group relative flex items-center rounded-xl text-sm transition-all duration-200 ease-in-out py-2.5 overflow-hidden select-none"
        :class="[
          sidebarCollapsed ? 'lg:px-0 lg:justify-center' : 'px-3 justify-between',
          activeModule === 'collection' 
            ? 'bg-emerald-50 text-emerald-900 font-semibold ring-1 ring-emerald-600/20 dark:bg-emerald-950/60 dark:text-emerald-300 dark:ring-emerald-500/30' 
            : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/70 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800/50 font-medium'
        ]"
        :title="sidebarCollapsed ? 'Collection Management' : ''"
      >
        <div class="flex items-center min-w-0 transition-all duration-300 ease-in-out" :class="sidebarCollapsed ? 'lg:gap-0' : 'gap-3'">
          <i 
            class="ph-bold ph-hand-coins text-lg shrink-0 transition-colors duration-200"
            :class="activeModule === 'collection' 
              ? 'text-emerald-700 dark:text-emerald-400' 
              : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300'"
          ></i>
          <span 
            class="sidebar-label whitespace-nowrap overflow-hidden transition-all duration-300 ease-in-out text-left"
            :class="[
              sidebarCollapsed ? 'lg:max-w-0 lg:opacity-0 lg:-translate-x-2' : 'max-w-[150px] opacity-100 translate-x-0',
              activeModule === 'collection' ? 'text-emerald-900 dark:text-emerald-200' : 'text-slate-700 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white'
            ]"
          >Collection</span>
        </div>

        <!-- Caret Indicator -->
        <div 
          class="sidebar-caret flex items-center transition-all duration-300 ease-in-out"
          :class="sidebarCollapsed ? 'lg:hidden' : 'opacity-100'"
        >
          <i 
            class="ph-bold ph-caret-down text-xs shrink-0 transition-transform duration-250 ease-in-out"
            :class="activeModule === 'collection' 
              ? 'rotate-180 text-emerald-700 dark:text-emerald-400' 
              : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300'"
          ></i>
        </div>

        <!-- Tooltip in Collapsed Mode -->
        <div 
          x-show="sidebarCollapsed" 
          x-cloak 
          class="hidden lg:group-hover:flex absolute left-full ml-3 top-1/2 -translate-y-1/2 z-50 items-center px-2.5 py-1.5 rounded-xl bg-slate-900 text-white text-xs font-semibold shadow-xl whitespace-nowrap pointer-events-none ring-1 ring-slate-800 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
        >
          <span>Collection Management</span>
          <span class="absolute right-full top-1/2 -translate-y-1/2 border-4 border-transparent border-r-slate-900 dark:border-r-slate-800"></span>
        </div>
      </button>

      <!-- Collection Sub-Menu Items (Smooth Collapse Animation) -->
      <div 
        x-show="activeModule === 'collection' && !sidebarCollapsed" 
        x-collapse.duration.250ms
        x-cloak
        class="sidebar-submenu ml-3.5 pl-3 border-l-2 border-slate-100 dark:border-slate-800 space-y-0.5 pt-1 pb-0.5"
      >
        <a 
          href="{{ route('collection.cashier-desk') }}" 
          class="group flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors duration-150 {{ (request()->routeIs('collection.cashier-desk*') || request()->routeIs('accounting.cashier*')) ? 'bg-emerald-50 text-emerald-800 font-semibold dark:bg-emerald-950/60 dark:text-emerald-300' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800/60' }}"
        >
          <div class="flex items-center gap-2 min-w-0">
            <i class="ph-bold ph-storefront text-sm shrink-0 {{ (request()->routeIs('collection.cashier-desk*') || request()->routeIs('accounting.cashier*')) ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300' }}"></i>
            <span class="truncate">Cashier POS Desk</span>
          </div>
          @if(request()->routeIs('collection.cashier-desk*') || request()->routeIs('accounting.cashier*'))
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400 shrink-0"></span>
          @endif
        </a>

        <a 
          href="{{ route('collection.receipts') }}" 
          class="group flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors duration-150 {{ request()->routeIs('collection.receipts*') ? 'bg-emerald-50 text-emerald-800 font-semibold dark:bg-emerald-950/60 dark:text-emerald-300' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800/60' }}"
        >
          <div class="flex items-center gap-2 min-w-0">
            <i class="ph-bold ph-receipt text-sm shrink-0 {{ request()->routeIs('collection.receipts*') ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300' }}"></i>
            <span class="truncate">Payment Receipts (OR)</span>
          </div>
          @if(request()->routeIs('collection.receipts*'))
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400 shrink-0"></span>
          @endif
        </a>

        <a 
          href="{{ route('collection.deposit-slips') }}" 
          class="group flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors duration-150 {{ request()->routeIs('collection.deposit-slips*') ? 'bg-emerald-50 text-emerald-800 font-semibold dark:bg-emerald-950/60 dark:text-emerald-300' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800/60' }}"
        >
          <div class="flex items-center gap-2 min-w-0">
            <i class="ph-bold ph-newspaper-clipping text-sm shrink-0 {{ request()->routeIs('collection.deposit-slips*') ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300' }}"></i>
            <span class="truncate">Deposit Slip Batches</span>
          </div>
          @if(request()->routeIs('collection.deposit-slips*'))
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400 shrink-0"></span>
          @endif
        </a>

        <a 
          href="{{ route('collection.bank-deposits') }}" 
          class="group flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors duration-150 {{ request()->routeIs('collection.bank-deposits*') ? 'bg-emerald-50 text-emerald-800 font-semibold dark:bg-emerald-950/60 dark:text-emerald-300' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800/60' }}"
        >
          <div class="flex items-center gap-2 min-w-0">
            <i class="ph-bold ph-bank text-sm shrink-0 {{ request()->routeIs('collection.bank-deposits*') ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300' }}"></i>
            <span class="truncate">Bank Deposits &amp; Clearing</span>
          </div>
          @if(request()->routeIs('collection.bank-deposits*'))
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400 shrink-0"></span>
          @endif
        </a>

        <a 
          href="{{ route('collection.payment-gateways') }}" 
          class="group flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors duration-150 {{ request()->routeIs('collection.payment-gateways*') ? 'bg-emerald-50 text-emerald-800 font-semibold dark:bg-emerald-950/60 dark:text-emerald-300' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800/60' }}"
        >
          <div class="flex items-center gap-2 min-w-0">
            <i class="ph-bold ph-cpu text-sm shrink-0 {{ request()->routeIs('collection.payment-gateways*') ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300' }}"></i>
            <span class="truncate">Payment Gateway Logs</span>
          </div>
          @if(request()->routeIs('collection.payment-gateways*'))
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400 shrink-0"></span>
          @endif
        </a>
      </div>
    </div>
    @endcan

    <!-- 5. Budget Management (Dropdown) -->
    @can('access-budget')
    <div class="sidebar-module-group">
      <button 
        type="button" 
        @click="toggleModule('budget')"
        class="sidebar-link w-full group relative flex items-center rounded-xl text-sm transition-all duration-200 ease-in-out py-2.5 overflow-hidden select-none"
        :class="[
          sidebarCollapsed ? 'lg:px-0 lg:justify-center' : 'px-3 justify-between',
          activeModule === 'budget' 
            ? 'bg-emerald-50 text-emerald-900 font-semibold ring-1 ring-emerald-600/20 dark:bg-emerald-950/60 dark:text-emerald-300 dark:ring-emerald-500/30' 
            : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/70 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800/50 font-medium'
        ]"
        :title="sidebarCollapsed ? 'Budget Management' : ''"
      >
        <div class="flex items-center min-w-0 transition-all duration-300 ease-in-out" :class="sidebarCollapsed ? 'lg:gap-0' : 'gap-3'">
          <i 
            class="ph-bold ph-calculator text-lg shrink-0 transition-colors duration-200"
            :class="activeModule === 'budget' 
              ? 'text-emerald-700 dark:text-emerald-400' 
              : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300'"
          ></i>
          <span 
            class="sidebar-label whitespace-nowrap overflow-hidden transition-all duration-300 ease-in-out text-left"
            :class="[
              sidebarCollapsed ? 'lg:max-w-0 lg:opacity-0 lg:-translate-x-2' : 'max-w-[150px] opacity-100 translate-x-0',
              activeModule === 'budget' ? 'text-emerald-900 dark:text-emerald-200' : 'text-slate-700 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white'
            ]"
          >Budget</span>
        </div>

        <!-- Caret Indicator -->
        <div 
          class="sidebar-caret flex items-center transition-all duration-300 ease-in-out"
          :class="sidebarCollapsed ? 'lg:hidden' : 'opacity-100'"
        >
          <i 
            class="ph-bold ph-caret-down text-xs shrink-0 transition-transform duration-250 ease-in-out"
            :class="activeModule === 'budget' 
              ? 'rotate-180 text-emerald-700 dark:text-emerald-400' 
              : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300'"
          ></i>
        </div>

        <!-- Tooltip in Collapsed Mode -->
        <div 
          x-show="sidebarCollapsed" 
          x-cloak 
          class="hidden lg:group-hover:flex absolute left-full ml-3 top-1/2 -translate-y-1/2 z-50 items-center px-2.5 py-1.5 rounded-xl bg-slate-900 text-white text-xs font-semibold shadow-xl whitespace-nowrap pointer-events-none ring-1 ring-slate-800 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
        >
          <span>Budget Management</span>
          <span class="absolute right-full top-1/2 -translate-y-1/2 border-4 border-transparent border-r-slate-900 dark:border-r-slate-800"></span>
        </div>
      </button>

      <!-- Budget Sub-Menu Items (Smooth Collapse Animation) -->
      <div 
        x-show="activeModule === 'budget' && !sidebarCollapsed" 
        x-collapse.duration.250ms
        x-cloak
        class="sidebar-submenu ml-3.5 pl-3 border-l-2 border-slate-100 dark:border-slate-800 space-y-0.5 pt-1 pb-0.5"
      >
        <a 
          href="{{ route('budget.fiscal-planning') }}" 
          class="group flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors duration-150 {{ request()->routeIs('budget.fiscal-planning*') ? 'bg-emerald-50 text-emerald-800 font-semibold dark:bg-emerald-950/60 dark:text-emerald-300' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800/60' }}"
        >
          <div class="flex items-center gap-2 min-w-0">
            <i class="ph-bold ph-calendar-check text-sm shrink-0 {{ request()->routeIs('budget.fiscal-planning*') ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300' }}"></i>
            <span class="truncate">Fiscal Year Planning</span>
          </div>
          @if(request()->routeIs('budget.fiscal-planning*'))
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400 shrink-0"></span>
          @endif
        </a>

        <a 
          href="{{ route('budget.budget-allocation') }}" 
          class="group flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors duration-150 {{ request()->routeIs('budget.budget-allocation*') ? 'bg-emerald-50 text-emerald-800 font-semibold dark:bg-emerald-950/60 dark:text-emerald-300' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800/60' }}"
        >
          <div class="flex items-center gap-2 min-w-0">
            <i class="ph-bold ph-chart-pie-slice text-sm shrink-0 {{ request()->routeIs('budget.budget-allocation*') ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300' }}"></i>
            <span class="truncate">Budget Allocation</span>
          </div>
          @if(request()->routeIs('budget.budget-allocation*'))
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400 shrink-0"></span>
          @endif
        </a>

        <a 
          href="{{ route('budget.departmental-budgets') }}" 
          class="group flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors duration-150 {{ request()->routeIs('budget.departmental-budgets*') ? 'bg-emerald-50 text-emerald-800 font-semibold dark:bg-emerald-950/60 dark:text-emerald-300' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800/60' }}"
        >
          <div class="flex items-center gap-2 min-w-0">
            <i class="ph-bold ph-users-three text-sm shrink-0 {{ request()->routeIs('budget.departmental-budgets*') ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300' }}"></i>
            <span class="truncate">Departmental Budgets</span>
          </div>
          @if(request()->routeIs('budget.departmental-budgets*'))
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400 shrink-0"></span>
          @endif
        </a>

        <a 
          href="{{ route('budget.variance-analysis') }}" 
          class="group flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors duration-150 {{ request()->routeIs('budget.variance-analysis*') ? 'bg-emerald-50 text-emerald-800 font-semibold dark:bg-emerald-950/60 dark:text-emerald-300' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800/60' }}"
        >
          <div class="flex items-center gap-2 min-w-0">
            <i class="ph-bold ph-trend-up text-sm shrink-0 {{ request()->routeIs('budget.variance-analysis*') ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300' }}"></i>
            <span class="truncate">Variance Analysis</span>
          </div>
          @if(request()->routeIs('budget.variance-analysis*'))
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400 shrink-0"></span>
          @endif
        </a>

        <a 
          href="{{ route('budget.reallocations') }}" 
          class="group flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors duration-150 {{ request()->routeIs('budget.reallocations*') ? 'bg-emerald-50 text-emerald-800 font-semibold dark:bg-emerald-950/60 dark:text-emerald-300' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800/60' }}"
        >
          <div class="flex items-center gap-2 min-w-0">
            <i class="ph-bold ph-arrows-left-right text-sm shrink-0 {{ request()->routeIs('budget.reallocations*') ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300' }}"></i>
            <span class="truncate">Budget Reallocations</span>
          </div>
          @if(request()->routeIs('budget.reallocations*'))
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400 shrink-0"></span>
          @endif
        </a>
      </div>
    </div>
    @endcan

    <!-- 6. General Ledger (GL Dropdown) -->
    @can('access-general-ledger')
    <div class="sidebar-module-group">
      <button 
        type="button" 
        @click="toggleModule('gl')"
        class="sidebar-link w-full group relative flex items-center rounded-xl text-sm transition-all duration-200 ease-in-out py-2.5 overflow-hidden select-none"
        :class="[
          sidebarCollapsed ? 'lg:px-0 lg:justify-center' : 'px-3 justify-between',
          activeModule === 'gl' 
            ? 'bg-emerald-50 text-emerald-900 font-semibold ring-1 ring-emerald-600/20 dark:bg-emerald-950/60 dark:text-emerald-300 dark:ring-emerald-500/30' 
            : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/70 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800/50 font-medium'
        ]"
        :title="sidebarCollapsed ? 'General Ledger' : ''"
      >
        <div class="flex items-center min-w-0 transition-all duration-300 ease-in-out" :class="sidebarCollapsed ? 'lg:gap-0' : 'gap-3'">
          <i 
            class="ph-bold ph-book-open-text text-lg shrink-0 transition-colors duration-200"
            :class="activeModule === 'gl' 
              ? 'text-emerald-700 dark:text-emerald-400' 
              : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300'"
          ></i>
          <span 
            class="sidebar-label whitespace-nowrap overflow-hidden transition-all duration-300 ease-in-out text-left"
            :class="[
              sidebarCollapsed ? 'lg:max-w-0 lg:opacity-0 lg:-translate-x-2' : 'max-w-[150px] opacity-100 translate-x-0',
              activeModule === 'gl' ? 'text-emerald-900 dark:text-emerald-200' : 'text-slate-700 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white'
            ]"
          >General Ledger</span>
        </div>

        <!-- Caret Indicator -->
        <div 
          class="sidebar-caret flex items-center transition-all duration-300 ease-in-out"
          :class="sidebarCollapsed ? 'lg:hidden' : 'opacity-100'"
        >
          <i 
            class="ph-bold ph-caret-down text-xs shrink-0 transition-transform duration-250 ease-in-out"
            :class="activeModule === 'gl' 
              ? 'rotate-180 text-emerald-700 dark:text-emerald-400' 
              : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300'"
          ></i>
        </div>

        <!-- Tooltip in Collapsed Mode -->
        <div 
          x-show="sidebarCollapsed" 
          x-cloak 
          class="hidden lg:group-hover:flex absolute left-full ml-3 top-1/2 -translate-y-1/2 z-50 items-center px-2.5 py-1.5 rounded-xl bg-slate-900 text-white text-xs font-semibold shadow-xl whitespace-nowrap pointer-events-none ring-1 ring-slate-800 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
        >
          <span>General Ledger</span>
          <span class="absolute right-full top-1/2 -translate-y-1/2 border-4 border-transparent border-r-slate-900 dark:border-r-slate-800"></span>
        </div>
      </button>

      <!-- GL Sub-Menu Items (Smooth Collapse Animation) -->
      <div 
        x-show="activeModule === 'gl' && !sidebarCollapsed" 
        x-collapse.duration.250ms
        x-cloak
        class="sidebar-submenu ml-3.5 pl-3 border-l-2 border-slate-100 dark:border-slate-800 space-y-0.5 pt-1 pb-0.5"
      >
        <a 
          href="{{ route('gl.journal-entries') }}" 
          class="group flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors duration-150 {{ (request()->routeIs('gl.journal-entries*') || request()->routeIs('accounting.general-ledger.*') || request()->routeIs('gl.post-entry*')) ? 'bg-emerald-50 text-emerald-800 font-semibold dark:bg-emerald-950/60 dark:text-emerald-300' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800/60' }}"
        >
          <div class="flex items-center gap-2 min-w-0">
            <i class="ph-bold ph-pencil-line text-sm shrink-0 {{ (request()->routeIs('gl.journal-entries*') || request()->routeIs('accounting.general-ledger.*') || request()->routeIs('gl.post-entry*')) ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300' }}"></i>
            <span class="truncate">Journal Entries</span>
          </div>
          @if(request()->routeIs('gl.journal-entries*'))
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400 shrink-0"></span>
          @endif
        </a>

        <a 
          href="{{ route('gl.chart-of-accounts') }}" 
          class="group flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors duration-150 {{ request()->routeIs('gl.chart-of-accounts*') ? 'bg-emerald-50 text-emerald-800 font-semibold dark:bg-emerald-950/60 dark:text-emerald-300' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800/60' }}"
        >
          <div class="flex items-center gap-2 min-w-0">
            <i class="ph-bold ph-tree-structure text-sm shrink-0 {{ request()->routeIs('gl.chart-of-accounts*') ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300' }}"></i>
            <span class="truncate">Chart of Accounts (COA)</span>
          </div>
          @if(request()->routeIs('gl.chart-of-accounts*'))
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400 shrink-0"></span>
          @endif
        </a>

        <a 
          href="{{ route('gl.ledger-books') }}" 
          class="group flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors duration-150 {{ request()->routeIs('gl.ledger-books*') ? 'bg-emerald-50 text-emerald-800 font-semibold dark:bg-emerald-950/60 dark:text-emerald-300' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800/60' }}"
        >
          <div class="flex items-center gap-2 min-w-0">
            <i class="ph-bold ph-books text-sm shrink-0 {{ request()->routeIs('gl.ledger-books*') ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300' }}"></i>
            <span class="truncate">Ledger Books</span>
          </div>
          @if(request()->routeIs('gl.ledger-books*'))
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400 shrink-0"></span>
          @endif
        </a>

        <a 
          href="{{ route('gl.trial-balance') }}" 
          class="group flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors duration-150 {{ request()->routeIs('gl.trial-balance*') ? 'bg-emerald-50 text-emerald-800 font-semibold dark:bg-emerald-950/60 dark:text-emerald-300' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800/60' }}"
        >
          <div class="flex items-center gap-2 min-w-0">
            <i class="ph-bold ph-chart-bar text-sm shrink-0 {{ request()->routeIs('gl.trial-balance*') ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300' }}"></i>
            <span class="truncate">Trial Balance</span>
          </div>
          @if(request()->routeIs('gl.trial-balance*'))
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400 shrink-0"></span>
          @endif
        </a>

        <a 
          href="{{ route('gl.period-end-closing') }}" 
          class="group flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors duration-150 {{ (request()->routeIs('gl.period-end-closing*') || request()->routeIs('accounting.period-close.*')) ? 'bg-emerald-50 text-emerald-800 font-semibold dark:bg-emerald-950/60 dark:text-emerald-300' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800/60' }}"
        >
          <div class="flex items-center gap-2 min-w-0">
            <i class="ph-bold ph-lock-key text-sm shrink-0 {{ (request()->routeIs('gl.period-end-closing*') || request()->routeIs('accounting.period-close.*')) ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300' }}"></i>
            <span class="truncate">Period-End Closing</span>
          </div>
          @if(request()->routeIs('gl.period-end-closing*'))
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400 shrink-0"></span>
          @endif
        </a>
      </div>
    </div>
    @endcan

    <!-- 7. User & Security (Dropdown) -->
    @can('access-user-management')
    <div class="sidebar-module-group">
      <button 
        type="button" 
        @click="toggleModule('userSecurity')"
        class="sidebar-link w-full group relative flex items-center rounded-xl text-sm transition-all duration-200 ease-in-out py-2.5 overflow-hidden select-none"
        :class="[
          sidebarCollapsed ? 'lg:px-0 lg:justify-center' : 'px-3 justify-between',
          activeModule === 'userSecurity' 
            ? 'bg-emerald-50 text-emerald-900 font-semibold ring-1 ring-emerald-600/20 dark:bg-emerald-950/60 dark:text-emerald-300 dark:ring-emerald-500/30' 
            : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/70 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800/50 font-medium'
        ]"
        :title="sidebarCollapsed ? 'User & Security' : ''"
      >
        <div class="flex items-center min-w-0 transition-all duration-300 ease-in-out" :class="sidebarCollapsed ? 'lg:gap-0' : 'gap-3'">
          <i 
            class="ph-bold ph-shield-check text-lg shrink-0 transition-colors duration-200"
            :class="activeModule === 'userSecurity' 
              ? 'text-emerald-700 dark:text-emerald-400' 
              : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300'"
          ></i>
          <span 
            class="sidebar-label whitespace-nowrap overflow-hidden transition-all duration-300 ease-in-out text-left"
            :class="[
              sidebarCollapsed ? 'lg:max-w-0 lg:opacity-0 lg:-translate-x-2' : 'max-w-[150px] opacity-100 translate-x-0',
              activeModule === 'userSecurity' ? 'text-emerald-900 dark:text-emerald-200' : 'text-slate-700 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white'
            ]"
          >User &amp; Security</span>
        </div>

        <div 
          class="sidebar-caret flex items-center gap-1.5 transition-all duration-300 ease-in-out"
          :class="sidebarCollapsed ? 'lg:hidden' : 'opacity-100'"
        >
          @if(($pendingWorkstationsCount ?? 0) > 0)
            <span class="rounded-md bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700 ring-1 ring-amber-600/20 dark:bg-amber-950/60 dark:text-amber-300">{{ $pendingWorkstationsCount }}</span>
          @endif
          <i 
            class="ph-bold ph-caret-down text-xs shrink-0 transition-transform duration-250 ease-in-out"
            :class="activeModule === 'userSecurity' 
              ? 'rotate-180 text-emerald-700 dark:text-emerald-400' 
              : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300'"
          ></i>
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

        <!-- Tooltip in Collapsed Mode -->
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
      </button>

      <!-- User & Security Sub-Menu Items (Smooth Collapse Animation) -->
      <div 
        x-show="activeModule === 'userSecurity' && !sidebarCollapsed" 
        x-collapse.duration.250ms
        x-cloak
        class="sidebar-submenu ml-3.5 pl-3 border-l-2 border-slate-100 dark:border-slate-800 space-y-0.5 pt-1 pb-0.5"
      >
        <a 
          href="{{ route('user-security.users') }}" 
          class="group flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors duration-150 {{ request()->routeIs('user-security.users*') ? 'bg-emerald-50 text-emerald-800 font-semibold dark:bg-emerald-950/60 dark:text-emerald-300' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800/60' }}"
        >
          <div class="flex items-center gap-2 min-w-0">
            <i class="ph-bold ph-user-gear text-sm shrink-0 {{ request()->routeIs('user-security.users*') ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300' }}"></i>
            <span class="truncate">User Accounts</span>
          </div>
          @if(request()->routeIs('user-security.users*'))
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400 shrink-0"></span>
          @endif
        </a>

        <a 
          href="{{ route('user-security.workstations') }}" 
          class="group flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors duration-150 {{ request()->routeIs('user-security.workstations*') ? 'bg-emerald-50 text-emerald-800 font-semibold dark:bg-emerald-950/60 dark:text-emerald-300' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800/60' }}"
        >
          <div class="flex items-center gap-2 min-w-0">
            <i class="ph-bold ph-desktop text-sm shrink-0 {{ request()->routeIs('user-security.workstations*') ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300' }}"></i>
            <span class="truncate">Workstation Binding</span>
          </div>
          <div class="flex items-center gap-1.5">
            @if(($pendingWorkstationsCount ?? 0) > 0)
              <span class="rounded-md bg-amber-50 px-1 py-0.2 text-[9px] font-bold text-amber-700 ring-1 ring-amber-600/20 dark:bg-amber-950/60 dark:text-amber-300">{{ $pendingWorkstationsCount }}</span>
            @endif
            @if(request()->routeIs('user-security.workstations*'))
              <span class="h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400 shrink-0"></span>
            @endif
          </div>
        </a>

        <a 
          href="{{ route('user-security.audit-trail') }}" 
          class="group flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors duration-150 {{ (request()->routeIs('user-security.audit-trail*') || request()->routeIs('accounting.audit-log*')) ? 'bg-emerald-50 text-emerald-800 font-semibold dark:bg-emerald-950/60 dark:text-emerald-300' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800/60' }}"
        >
          <div class="flex items-center gap-2 min-w-0">
            <i class="ph-bold ph-lock-key text-sm shrink-0 {{ (request()->routeIs('user-security.audit-trail*') || request()->routeIs('accounting.audit-log*')) ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300' }}"></i>
            <span class="truncate">CAS Audit Trail</span>
          </div>
          @if(request()->routeIs('user-security.audit-trail*'))
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400 shrink-0"></span>
          @endif
        </a>
      </div>
    </div>
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

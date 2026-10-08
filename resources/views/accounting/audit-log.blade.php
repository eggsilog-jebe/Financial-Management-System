@extends('layouts.app')

@section('title', 'System Audit Trail & Compliance')
@section('module', 'general-ledger')
@section('page', 'audit-log')

@section('content')
<div class="space-y-6" x-data="{
  init() {
    this.$watch('isOpen', val => {
      document.body.classList.toggle('overflow-hidden', val);
      document.body.classList.toggle('modal-open', val);
    });
  },
  isOpen: false,
  activeLog: null,
  openDiff(data) {
    this.activeLog = data;
    this.isOpen = true;
  },
  closeDiff() {
    this.isOpen = false;
    this.activeLog = null;
  }
}" @keydown.escape.window="closeDiff()">

  {{-- Flash Messages --}}
  @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-transition class="flex items-center justify-between gap-3 rounded-2xl border border-emerald-200/80 bg-emerald-50/80 p-4 text-xs text-emerald-800 shadow-sm dark:border-emerald-800/40 dark:bg-emerald-950/40 dark:text-emerald-300">
      <div class="flex items-center gap-2.5">
        <i class="ph-fill ph-check-circle text-lg text-emerald-600 dark:text-emerald-400 shrink-0"></i>
        <span>{!! session('success') !!}</span>
      </div>
      <button @click="show = false" type="button" class="text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-200 transition-colors">
        <i class="ph ph-x text-sm"></i>
      </button>
    </div>
  @endif

  @if(session('warning'))
    <div x-data="{ show: true }" x-show="show" x-transition class="flex items-center justify-between gap-3 rounded-2xl border border-amber-200/80 bg-amber-50/80 p-4 text-xs text-amber-800 shadow-sm dark:border-amber-800/40 dark:bg-amber-950/40 dark:text-amber-300">
      <div class="flex items-center gap-2.5">
        <i class="ph-fill ph-warning text-lg text-amber-600 dark:text-amber-400 shrink-0"></i>
        <span>{!! session('warning') !!}</span>
      </div>
      <button @click="show = false" type="button" class="text-amber-600 hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-200 transition-colors">
        <i class="ph ph-x text-sm"></i>
      </button>
    </div>
  @endif

  {{-- Security Incident Banner with Acknowledge Action --}}
  @if(($stats['failed_logins'] ?? 0) >= 5)
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 rounded-2xl p-4 shadow-sm transition-all
                {{ ($unacknowledgedFailedLogins ?? 0) > 0 
                    ? 'bg-rose-50 ring-1 ring-rose-300/70 dark:bg-rose-950/30 dark:ring-rose-800/60' 
                    : 'bg-emerald-50/60 ring-1 ring-emerald-200/80 dark:bg-emerald-950/20 dark:ring-emerald-800/40' }}">
      <div class="flex items-center gap-3">
        <span class="inline-flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl
                     {{ ($unacknowledgedFailedLogins ?? 0) > 0 
                         ? 'bg-rose-100 text-rose-600 dark:bg-rose-900/50 dark:text-rose-300' 
                         : 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/50 dark:text-emerald-300' }}">
          <i class="ph-bold {{ ($unacknowledgedFailedLogins ?? 0) > 0 ? 'ph-warning' : 'ph-check-circle' }} text-base"></i>
        </span>
        <div>
          <p class="text-sm font-semibold {{ ($unacknowledgedFailedLogins ?? 0) > 0 ? 'text-rose-900 dark:text-rose-200' : 'text-emerald-900 dark:text-emerald-200' }}">
            @if(($unacknowledgedFailedLogins ?? 0) > 0)
              Active Security Incident — {{ $stats['failed_logins'] }} Failed Login Attempts Today
            @else
              Security Incident Reviewed &amp; Acknowledged
            @endif
          </p>
          <p class="text-xs {{ ($unacknowledgedFailedLogins ?? 0) > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
            @if(($unacknowledgedFailedLogins ?? 0) > 0)
              These attempts triggered high-priority security telemetry across the portal.
            @else
              Incident was acknowledged at {{ \Carbon\Carbon::parse($acknowledgedAt)->format('h:i A') }}. Dashboard warning has been dismissed.
            @endif
          </p>
        </div>
      </div>

      <div class="flex items-center gap-2 self-start sm:self-auto">
        @if(($unacknowledgedFailedLogins ?? 0) > 0)
          <form method="POST" action="{{ route('accounting.audit-log.acknowledge') }}">
            @csrf
            <button type="submit"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-emerald-500/50">
              <i class="ph-bold ph-check-circle"></i>
              Acknowledge Incident
            </button>
          </form>
        @else
          <span class="inline-flex items-center gap-1 rounded-xl bg-emerald-100/80 dark:bg-emerald-900/40 px-3 py-1.5 text-xs font-medium text-emerald-800 dark:text-emerald-300">
            <i class="ph-bold ph-check text-xs"></i>
            Acknowledged for this session
          </span>
        @endif
      </div>
    </div>
  @endif

  {{-- Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <div class="flex items-center gap-2 text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">
        <a href="{{ route('accounting.dashboard') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">Overview</a>
        <i class="ph ph-caret-right text-[10px]"></i>
        <a href="{{ route('user-security.users') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">Security & Compliance</a>
        <i class="ph ph-caret-right text-[10px]"></i>
        <span class="text-slate-900 dark:text-slate-200 font-semibold">Audit Trail</span>
      </div>
      <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
        System Audit Trail &amp; Compliance Logs
      </h1>
    </div>
    <div class="flex items-center gap-2.5">
      <span class="inline-flex items-center gap-1.5 rounded-xl border border-rose-200/80 bg-rose-50/80 px-3 py-1.5 text-xs font-semibold text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-300">
        <i class="ph-fill ph-lock-key text-rose-500"></i>
        Restricted: CFO &amp; Auditor
      </span>
      <a href="{{ route('accounting.dashboard') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 transition-all">
        <i class="ph ph-arrow-left"></i>
        Back to Dashboard
      </a>
    </div>
  </div>

  {{-- KPI Overview Cards --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    {{-- Total Records --}}
    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
      <div class="flex items-center justify-between">
        <span class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Audit Records</span>
        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
          <i class="ph-duotone ph-list-dashes text-lg"></i>
        </div>
      </div>
      <div class="mt-2">
        <span class="text-2xl font-bold font-sans kpi-value tabular-nums text-slate-900 dark:text-white">{{ number_format($stats['total_logs']) }}</span>
      </div>
      <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Immutable ledger events</p>
    </div>

    {{-- Successful Logins --}}
    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
      <div class="flex items-center justify-between">
        <span class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Successful Logins Today</span>
        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
          <i class="ph-duotone ph-sign-in text-lg"></i>
        </div>
      </div>
      <div class="mt-2">
        <span class="text-2xl font-bold font-sans kpi-value tabular-nums text-emerald-600 dark:text-emerald-400">{{ number_format($stats['logins_today']) }}</span>
      </div>
      <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Authorized personnel sessions</p>
    </div>

    {{-- Failed Logins --}}
    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
      <div class="flex items-center justify-between">
        <span class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Failed Logins Today</span>
        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400">
          <i class="ph-duotone ph-warning-octagon text-lg"></i>
        </div>
      </div>
      <div class="mt-2">
        <span class="text-2xl font-bold font-sans kpi-value tabular-nums text-rose-600 dark:text-rose-400">{{ number_format($stats['failed_logins']) }}</span>
      </div>
      <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Security challenge rejections</p>
    </div>

    {{-- Activities & Changes --}}
    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
      <div class="flex items-center justify-between">
        <span class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Activities &amp; Changes Today</span>
        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400">
          <i class="ph-duotone ph-database text-lg"></i>
        </div>
      </div>
      <div class="mt-2">
        <span class="text-2xl font-bold font-sans kpi-value tabular-nums text-amber-600 dark:text-amber-400">{{ number_format($stats['mutations_today']) }}</span>
      </div>
      <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Transactions &amp; mutations logged</p>
    </div>
  </div>

  

  {{-- Audit Log Table Card --}}
  <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800 overflow-hidden">
    {{-- Filter Toolbar --}}
    <div class="border-b border-slate-200 p-4 dark:border-slate-800">
      <form method="GET" action="{{ route('accounting.audit-log') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-center">

        {{-- Search --}}
        <div class="lg:col-span-3">
          <div class="relative">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
              <i class="ph ph-magnifying-glass"></i>
            </div>
            <input type="search" name="search" class="block w-full rounded-xl border-0 bg-slate-50 py-1.5 pl-9 pr-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700" placeholder="Search logs, user, IP..." value="{{ $search }}">
          </div>
        </div>

        {{-- Event --}}
        <div class="lg:col-span-2">
          <select name="event" onchange="this.form.submit()" class="block w-full rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-800 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700 cursor-pointer {{ $event ? 'font-semibold text-emerald-600 dark:text-emerald-400' : '' }}" aria-label="Filter by Event">
            <option value="">All Events</option>
            @foreach($eventGroups as $groupName => $groupItems)
              <optgroup label="{{ $groupName }}" class="font-bold text-slate-500 bg-slate-50 dark:bg-slate-900">
                @foreach($groupItems as $evKey => $evLabel)
                  <option value="{{ $evKey }}" class="font-normal text-slate-800 dark:text-slate-100" @selected($event === $evKey)>{{ $evLabel }}</option>
                @endforeach
              </optgroup>
            @endforeach
          </select>
        </div>

        {{-- Module --}}
        <div class="lg:col-span-2">
          <select name="module" onchange="this.form.submit()" class="block w-full rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-800 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700 cursor-pointer {{ $module ? 'font-semibold text-emerald-600 dark:text-emerald-400' : '' }}" aria-label="Filter by Module">
            <option value="">All Modules</option>
            @foreach($modules as $mod)
              @if(is_string($mod) && $mod !== '')
                <option value="{{ $mod }}" @selected($module === $mod)>{{ $mod }}</option>
              @endif
            @endforeach
          </select>
        </div>

        {{-- Role --}}
        <div class="lg:col-span-2">
          <select name="role" onchange="this.form.submit()" class="block w-full rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-800 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700 cursor-pointer {{ $role ? 'font-semibold text-emerald-600 dark:text-emerald-400' : '' }}" aria-label="Filter by Role">
            <option value="">All Roles</option>
            @foreach($roles as $r)
              @if(is_string($r) && $r !== '')
                <option value="{{ $r }}" @selected($role === $r)>{{ $r }}</option>
              @endif
            @endforeach
          </select>
        </div>

        {{-- Date --}}
        <div class="lg:col-span-1">
          <input type="date" name="date_from" onchange="this.form.submit()" class="block w-full rounded-xl border-0 bg-slate-50 py-1.5 px-2 text-xs font-semibold text-slate-800 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700 cursor-pointer" value="{{ $dateFrom }}" title="Filter from date">
        </div>

        {{-- Actions --}}
        <div class="lg:col-span-2 flex items-center justify-end gap-2">
          <button type="submit" class="inline-flex w-full items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 transition-all cursor-pointer">
            <i class="ph ph-funnel"></i>
            Filter
          </button>
          @if($search || $event || $module || $role || $dateFrom)
            <a href="{{ route('accounting.audit-log') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white p-1.5 text-xs font-semibold text-slate-600 shadow-sm hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 transition-all" title="Reset all filters">
              <i class="ph ph-arrow-counter-clockwise"></i>
            </a>
          @endif
        </div>
      </form>

      {{-- Active Filter Chips --}}
      @if($search || $event || $module || $role || $dateFrom)
        <div class="flex flex-wrap items-center gap-2 pt-3 mt-3 border-t border-slate-100 dark:border-slate-800/80 text-xs">
          <span class="text-slate-400 text-[11px] font-medium flex items-center gap-1">
            <i class="ph ph-sliders-horizontal"></i>
            Active Filters:
          </span>
          @if($search)
            <a href="{{ request()->fullUrlWithQuery(['search' => null]) }}" class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2.5 py-1 text-[11px] font-medium text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 transition-colors">
              <span>Search: "{{ Str::limit($search, 16) }}"</span>
              <i class="ph ph-x text-[10px]"></i>
            </a>
          @endif
          @if($event)
            <a href="{{ request()->fullUrlWithQuery(['event' => null]) }}" class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2.5 py-1 text-[11px] font-medium text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 transition-colors">
              <span>Event: {{ ucfirst(str_replace('_', ' ', $event)) }}</span>
              <i class="ph ph-x text-[10px]"></i>
            </a>
          @endif
          @if($module)
            <a href="{{ request()->fullUrlWithQuery(['module' => null]) }}" class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2.5 py-1 text-[11px] font-medium text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 transition-colors">
              <span>Module: {{ $module }}</span>
              <i class="ph ph-x text-[10px]"></i>
            </a>
          @endif
          @if($role)
            <a href="{{ request()->fullUrlWithQuery(['role' => null]) }}" class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2.5 py-1 text-[11px] font-medium text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 transition-colors">
              <span>Role: {{ $role }}</span>
              <i class="ph ph-x text-[10px]"></i>
            </a>
          @endif
          @if($dateFrom)
            <a href="{{ request()->fullUrlWithQuery(['date_from' => null]) }}" class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2.5 py-1 text-[11px] font-medium text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 transition-colors">
              <span>Date: {{ $dateFrom }}</span>
              <i class="ph ph-x text-[10px]"></i>
            </a>
          @endif
          <a href="{{ route('accounting.audit-log') }}" class="text-xs text-rose-600 hover:underline dark:text-rose-400 font-medium ml-1">Reset all</a>
        </div>
      @endif
    </div>
    <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
          <i class="ph ph-clock-counter-clockwise text-slate-400"></i>
          Audit Trail Records
          <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-mono font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ number_format($logs->total()) }} total</span>
        </h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Records are cryptographically immutable and sequentially time-stamped in UTC.</p>
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse text-xs">
        <thead>
          <tr class="border-b border-slate-200/80 bg-slate-50/75 dark:border-slate-800 dark:bg-slate-950/50">
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px] w-40">Timestamp (UTC)</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px] w-48">User &amp; Role</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px] w-32">Event</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px] w-36">Module</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Description</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px] w-32">IP Address</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px] text-right w-20">Details</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
          @forelse($logs as $log)
            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
              {{-- Timestamp --}}
              <td class="py-3 px-4 whitespace-nowrap">
                <div class="font-semibold text-slate-900 dark:text-white">{{ $log->created_at->format('M d, Y') }}</div>
                <div class="text-[11px] font-mono text-slate-400">{{ $log->created_at->format('H:i:s') }}</div>
              </td>

              {{-- User & Role --}}
              <td class="py-3 px-4">
                <div class="font-semibold text-slate-900 dark:text-white truncate max-w-[180px]" title="{{ $log->user_name }}">
                  {{ $log->user_name ?? 'System' }}
                </div>
                @php
                  $roleBadge = match($log->user_role) {
                    'CFO' => 'bg-rose-50 text-rose-700 ring-rose-700/10 dark:bg-rose-950/60 dark:text-rose-400 dark:ring-rose-500/20',
                    'FinanceManager' => 'bg-blue-50 text-blue-700 ring-blue-700/10 dark:bg-blue-950/60 dark:text-blue-400 dark:ring-blue-500/20',
                    'StaffAccountant' => 'bg-emerald-50 text-emerald-700 ring-emerald-700/10 dark:bg-emerald-950/60 dark:text-emerald-400 dark:ring-emerald-500/20',
                    'BillingClerk' => 'bg-cyan-50 text-cyan-700 ring-cyan-700/10 dark:bg-cyan-950/60 dark:text-cyan-400 dark:ring-cyan-500/20',
                    'Cashier' => 'bg-amber-50 text-amber-700 ring-amber-700/10 dark:bg-amber-950/60 dark:text-amber-400 dark:ring-amber-500/20',
                    'Auditor' => 'bg-purple-50 text-purple-700 ring-purple-700/10 dark:bg-purple-950/60 dark:text-purple-400 dark:ring-purple-500/20',
                    default => 'bg-slate-100 text-slate-700 ring-slate-700/10 dark:bg-slate-800 dark:text-slate-400 dark:ring-slate-700/20'
                  };
                @endphp
                <span class="inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-semibold ring-1 ring-inset {{ $roleBadge }} mt-0.5">
                  {{ $log->user_role ?? 'System / Daemon' }}
                </span>
              </td>

              {{-- Event Badge --}}
              <td class="py-3 px-4">
                @php
                  $eventClass = match($log->event) {
                    'login' => 'bg-emerald-50 text-emerald-700 ring-emerald-700/10 dark:bg-emerald-950/60 dark:text-emerald-400 dark:ring-emerald-500/20',
                    'logout' => 'bg-slate-100 text-slate-700 ring-slate-700/10 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700/20',
                    'failed_login' => 'bg-rose-50 text-rose-700 ring-rose-700/10 dark:bg-rose-950/60 dark:text-rose-400 dark:ring-rose-500/20',
                    '2fa_passed' => 'bg-blue-50 text-blue-700 ring-blue-700/10 dark:bg-blue-950/60 dark:text-blue-400 dark:ring-blue-500/20',
                    'session_displaced', 'session_terminated' => 'bg-purple-50 text-purple-700 ring-purple-700/10 dark:bg-purple-950/60 dark:text-purple-400 dark:ring-purple-500/20',
                    'created' => 'bg-emerald-50 text-emerald-700 ring-emerald-700/10 dark:bg-emerald-950/60 dark:text-emerald-400 dark:ring-emerald-500/20',
                    'updated' => 'bg-amber-50 text-amber-700 ring-amber-700/10 dark:bg-amber-950/60 dark:text-amber-400 dark:ring-amber-500/20',
                    'deleted' => 'bg-rose-50 text-rose-700 ring-rose-700/10 dark:bg-rose-950/60 dark:text-rose-400 dark:ring-rose-500/20',
                    'posted' => 'bg-cyan-50 text-cyan-700 ring-cyan-700/10 dark:bg-cyan-950/60 dark:text-cyan-400 dark:ring-cyan-500/20',
                    'reversed' => 'bg-slate-900 text-white dark:bg-slate-700',
                    'viewed' => 'bg-slate-100 text-slate-600 ring-slate-600/10 dark:bg-slate-800 dark:text-slate-400',
                    'exported', 'printed' => 'bg-indigo-50 text-indigo-700 ring-indigo-700/10 dark:bg-indigo-950/60 dark:text-indigo-400 dark:ring-indigo-500/20',
                    'approved' => 'bg-emerald-50 text-emerald-700 ring-emerald-700/10 dark:bg-emerald-950/60 dark:text-emerald-400 dark:ring-emerald-500/20',
                    'rejected' => 'bg-rose-50 text-rose-700 ring-rose-700/10 dark:bg-rose-950/60 dark:text-rose-400 dark:ring-rose-500/20',
                    'revoked' => 'bg-amber-50 text-amber-700 ring-amber-700/10 dark:bg-amber-950/60 dark:text-amber-400 dark:ring-amber-500/20',
                    'collected' => 'bg-teal-50 text-teal-700 ring-teal-700/10 dark:bg-teal-950/60 dark:text-teal-400 dark:ring-teal-500/20',
                    'closed', 'locked' => 'bg-slate-900 text-white dark:bg-slate-700',
                    default => 'bg-slate-100 text-slate-700 ring-slate-700/10 dark:bg-slate-800 dark:text-slate-300'
                  };
                @endphp
                <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-mono font-semibold ring-1 ring-inset {{ $eventClass }}">
                  {{ strtoupper(str_replace('_', ' ', $log->event)) }}
                </span>
              </td>

              {{-- Module --}}
              <td class="py-3 px-4">
                <span class="inline-flex items-center gap-1.5 font-medium text-slate-700 dark:text-slate-300">
                  <i class="ph ph-folder text-slate-400"></i>
                  {{ $log->module }}
                </span>
              </td>

              {{-- Description --}}
              <td class="py-3 px-4">
                <div class="text-slate-900 dark:text-slate-100 font-medium">{{ $log->description }}</div>
                @if($log->auditable_type)
                  <div class="text-[11px] font-mono text-slate-400 mt-0.5">
                    Ref: {{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}
                  </div>
                @endif
              </td>

              {{-- IP Address --}}
              <td class="py-3 px-4">
                <code class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[11px] text-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ $log->ip_address ?? '127.0.0.1' }}</code>
              </td>

              {{-- Diff Details Trigger --}}
              <td class="py-3 px-4 text-right">
                @if(!empty($log->new_values) || !empty($log->old_values))
                  <button type="button" @click="openDiff({
                    id: {{ $log->id }},
                    event: '{{ $log->event }}',
                    user: '{{ addslashes($log->user_name ?? 'System') }}',
                    oldValues: @js($log->old_values),
                    newValues: @js($log->new_values),
                    userAgent: @js($log->user_agent ?? 'N/A')
                  })" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white p-1.5 text-slate-600 shadow-sm hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 transition-all" title="View State Changes">
                    <i class="ph ph-code text-sm"></i>
                  </button>
                @else
                  <span class="text-slate-400 font-mono text-xs">—</span>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-10 text-slate-500 dark:text-slate-400">
                <div class="flex flex-col items-center justify-center">
                  <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-slate-800 mb-2">
                    <i class="ph ph-folder-open text-xl"></i>
                  </div>
                  <span class="font-medium text-xs text-slate-700 dark:text-slate-300">No audit log records match the selected filter criteria</span>
                  <a href="{{ route('accounting.audit-log') }}" class="text-xs text-emerald-600 dark:text-emerald-400 hover:underline mt-1 font-medium">Reset all filters</a>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="p-4 border-t border-slate-100 dark:border-slate-800/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      @if($logs->hasPages())
        <div class="text-xs text-slate-500 dark:text-slate-400">
          Showing <span class="font-mono font-bold text-slate-800 dark:text-slate-200">{{ $logs->firstItem() }}</span> to <span class="font-mono font-bold text-slate-800 dark:text-slate-200">{{ $logs->lastItem() }}</span> of <span class="font-mono font-bold text-slate-800 dark:text-slate-200">{{ number_format($logs->total()) }}</span> records (5 per page)
        </div>
        <div>
          {{ $logs->links() }}
        </div>
      @else
        <div class="flex items-center justify-between w-full text-xs text-slate-500 dark:text-slate-400">
          <p class="font-medium">
            Showing <span class="font-mono font-bold text-slate-800 dark:text-slate-200">{{ $logs->firstItem() ?? 0 }}</span>
            to <span class="font-mono font-bold text-slate-800 dark:text-slate-200">{{ $logs->lastItem() ?? 0 }}</span>
            of <span class="font-mono font-bold text-slate-800 dark:text-slate-200">{{ number_format($logs->total()) }}</span> records (5 per page)
          </p>
          <span class="text-[11px] font-mono text-slate-400">Page 1 of 1</span>
        </div>
      @endif
    </div>
  </div>

  {{-- State Changes Alpine Modal --}}
  <template x-teleport="body">
  <div x-show="isOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div x-show="isOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-950/60 backdrop-blur-md transition-opacity" @click="closeDiff()"></div>

    <div class="fixed inset-0 z-10 w-screen overflow-y-auto p-4 sm:p-6 md:p-20 flex min-h-full items-center justify-center pointer-events-none">
      <div x-show="isOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" @click.outside="closeDiff()" class="pointer-events-auto relative mx-auto w-full max-w-4xl transform rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800 transition-all text-left">

        <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
          <div class="flex items-center gap-2.5">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
              <i class="ph-duotone ph-git-diff text-xl"></i>
            </div>
            <div>
              <h3 class="text-sm font-bold text-slate-900 dark:text-white" id="modal-title">
                State Diff: Log #<span x-text="activeLog?.id"></span> (<span class="font-mono text-emerald-600 dark:text-emerald-400" x-text="activeLog?.event"></span>)
              </h3>
              <p class="text-[11px] text-slate-400">Actor: <span class="font-semibold text-slate-700 dark:text-slate-300" x-text="activeLog?.user"></span></p>
            </div>
          </div>
          <button @click="closeDiff()" type="button" class="rounded-lg p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
            <i class="ph ph-x text-base"></i>
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 my-5">
          {{-- Previous Values --}}
          <div>
            <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2 flex items-center gap-1.5">
              <i class="ph ph-clock-counter-clockwise text-slate-400"></i>
              Previous Values (Original)
            </div>
            <pre class="w-full rounded-xl border border-slate-200 bg-slate-50/75 p-3.5 text-xs font-mono text-slate-800 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200 overflow-y-auto max-h-72 leading-relaxed" x-text="activeLog?.oldValues ? JSON.stringify(activeLog.oldValues, null, 2) : 'None (New Record Created)'"></pre>
          </div>

          {{-- Modified Values --}}
          <div>
            <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2 flex items-center gap-1.5">
              <i class="ph ph-check-circle text-emerald-500"></i>
              New / Modified Values
            </div>
            <pre class="w-full rounded-xl border border-slate-200 bg-slate-50/75 p-3.5 text-xs font-mono text-slate-800 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200 overflow-y-auto max-h-72 leading-relaxed" x-text="activeLog?.newValues ? JSON.stringify(activeLog.newValues, null, 2) : 'None (Record Purged / Deleted)'"></pre>
          </div>
        </div>

        <div class="rounded-xl border border-slate-100 bg-slate-50 p-3 text-[11px] text-slate-600 dark:border-slate-800 dark:bg-slate-950/60 dark:text-slate-400 flex items-center gap-2">
          <span class="font-semibold text-slate-700 dark:text-slate-300">User Agent:</span>
          <span class="font-mono truncate" x-text="activeLog?.userAgent"></span>
        </div>

        <div class="mt-5 flex justify-end">
          <button @click="closeDiff()" type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 transition-all">
            Close
          </button>
        </div>

      </div>
    </div>
  </div>
  </template>

</div>
@endsection

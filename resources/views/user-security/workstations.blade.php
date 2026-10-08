@extends('layouts.app')

@section('title', 'Workstation & Session Security — User & Security Management')

@section('content')
<div class="space-y-6" x-data="{
  init() {
    this.$watch('confirmModal.open', val => {
      document.body.classList.toggle('overflow-hidden', val);
      document.body.classList.toggle('modal-open', val);
    });
  },
  confirmModal: {
    open: false,
    title: '',
    message: '',
    detail: '',
    targetName: '',
    targetIp: '',
    badgeText: 'Irreversible',
    confirmText: 'Confirm',
    confirmVariant: 'danger',
    actionUrl: '',
    method: 'POST',
    isSubmitting: false,
    openModal(config) {
      this.title = config.title || 'Confirm Action';
      this.message = config.message || 'Are you sure you want to proceed?';
      this.detail = config.detail || '';
      this.targetName = config.targetName || '';
      this.targetIp = config.targetIp || '';
      this.badgeText = config.badgeText || (config.confirmVariant === 'warning' ? 'Warning' : 'Irreversible');
      this.confirmText = config.confirmText || 'Confirm';
      this.confirmVariant = config.confirmVariant || 'danger';
      this.actionUrl = config.actionUrl || '';
      this.method = config.method || 'POST';
      this.isSubmitting = false;
      this.open = true;
    },
    close() {
      if (!this.isSubmitting) {
        this.open = false;
      }
    }
  }
}">

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

  @if(isset($errors) && $errors->any())
    <div x-data="{ show: true }" x-show="show" x-transition class="flex items-center justify-between gap-3 rounded-2xl border border-rose-200/80 bg-rose-50/80 p-4 text-xs text-rose-800 shadow-sm dark:border-rose-800/40 dark:bg-rose-950/40 dark:text-rose-300">
      <div class="flex items-center gap-2.5">
        <i class="ph-fill ph-warning-circle text-lg text-rose-600 dark:text-rose-400 shrink-0"></i>
        <span>{{ $errors->first() }}</span>
      </div>
      <button @click="show = false" type="button" class="text-rose-600 hover:text-rose-800 dark:text-rose-400 dark:hover:text-rose-200 transition-colors">
        <i class="ph ph-x text-sm"></i>
      </button>
    </div>
  @endif

  {{-- Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <div class="flex items-center gap-2 text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">
        <a href="{{ route('accounting.dashboard') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">Overview</a>
        <i class="ph ph-caret-right text-[10px]"></i>
        <a href="{{ route('user-security.users') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">User & Security</a>
        <i class="ph ph-caret-right text-[10px]"></i>
        <span class="text-slate-900 dark:text-slate-200 font-semibold">Workstations</span>
      </div>
      <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
        Workstation &amp; Session Security
      </h1>
    </div>
    <div class="flex items-center gap-2">
      <a href="{{ route('user-security.users') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 transition-all">
        <i class="ph ph-users"></i>
        User Accounts
      </a>
      <a href="{{ route('user-security.audit-trail') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 transition-all">
        <i class="ph ph-clock-countdown"></i>
        Audit Trail
      </a>
    </div>
  </div>

  {{-- Live Metrics Cards --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    {{-- Pending Authorizations --}}
    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800 {{ $metrics['pending_count'] > 0 ? 'border-l-4 border-l-amber-500' : '' }}">
      <div class="flex items-center justify-between">
        <span class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pending Authorizations</span>
        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400">
          <i class="ph-duotone ph-broadcast text-lg"></i>
        </div>
      </div>
      <div class="mt-2 flex items-baseline gap-2">
        <span class="text-2xl font-bold font-sans kpi-value tabular-nums text-amber-600 dark:text-amber-400" id="stat-pending-count">{{ $metrics['pending_count'] }}</span>
        @if($metrics['pending_count'] > 0)
          <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-800 dark:bg-amber-900/60 dark:text-amber-200 animate-pulse">
            Action Required
          </span>
        @endif
      </div>
      <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Real-time approval requests</p>
    </div>

    {{-- Bound Workstations --}}
    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
      <div class="flex items-center justify-between">
        <span class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Bound Workstations</span>
        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
          <i class="ph-duotone ph-desktop text-lg"></i>
        </div>
      </div>
      <div class="mt-2">
        <span class="text-2xl font-bold font-sans kpi-value tabular-nums text-emerald-600 dark:text-emerald-400">{{ $metrics['approved_count'] }}</span>
      </div>
      <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Active authorized computers</p>
    </div>

    {{-- Live Active Sessions --}}
    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
      <div class="flex items-center justify-between">
        <span class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Live Active Sessions</span>
        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
          <i class="ph-duotone ph-users-three text-lg"></i>
        </div>
      </div>
      <div class="mt-2">
        <span class="text-2xl font-bold font-sans kpi-value tabular-nums text-blue-600 dark:text-blue-400" id="stat-active-count">{{ $metrics['active_sessions'] }}</span>
      </div>
      <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Enforcing single active session</p>
    </div>

    {{-- Hardware Policy --}}
    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
      <div class="flex items-center justify-between">
        <span class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Hardware Policy</span>
        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-950/60 dark:text-purple-400">
          <i class="ph-duotone ph-shield-check text-lg"></i>
        </div>
      </div>
      <div class="mt-2 flex items-baseline gap-1.5">
        <span class="text-2xl font-bold font-sans kpi-value tabular-nums text-slate-900 dark:text-white">1–3</span>
        <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">per user</span>
      </div>
      <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Max 3 bound terminals / personnel</p>
    </div>
  </div>

  

  {{-- Section 1: Real-Time Pending Workstations --}}
  <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800 overflow-hidden">
    <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
          <i class="ph-fill ph-broadcast text-amber-500"></i>
          Real-Time Pending Workstation Requests
          <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-mono font-semibold text-amber-800 dark:bg-amber-900/60 dark:text-amber-200" id="badge-pending-count">{{ $pendingWorkstations->count() }}</span>
        </h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">New or unrecognized workstations awaiting Super Admin authorization before login.</p>
      </div>
      <div class="flex items-center gap-2">
        <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-medium text-slate-600 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-400">
          <i class="ph ph-arrows-clockwise text-xs animate-spin text-emerald-600 dark:text-emerald-400" style="animation-duration: 3s;"></i>
          Live Polling
        </span>
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse text-xs" id="pending-table">
        <thead>
          <tr class="border-b border-slate-200/80 bg-slate-50/75 dark:border-slate-800 dark:bg-slate-950/50">
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Personnel</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Workstation / OS</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Browser</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">IP Address</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Quota Status</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Requested</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px] text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60" id="pending-tbody">
          @forelse($pendingWorkstations as $pw)
            <tr id="pending-row-{{ $pw->id }}" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
              <td class="py-3 px-4">
                <div class="font-semibold text-slate-900 dark:text-white">{{ $pw->user?->name }}</div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-1.5 mt-0.5">
                  <span class="font-mono">{{ $pw->user?->email }}</span>
                  <span>&bull;</span>
                  <span class="inline-flex items-center rounded bg-slate-100 px-1.5 py-0.2 text-[10px] font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-400">{{ $pw->user?->roleLabel() }}</span>
                </div>
              </td>
              <td class="py-3 px-4">
                <div class="font-medium text-slate-900 dark:text-white">{{ $pw->workstation_name }}</div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 font-mono">{{ $pw->platform ?? 'Unknown OS' }}</div>
              </td>
              <td class="py-3 px-4">
                <span class="inline-flex items-center rounded-lg border border-slate-200 bg-slate-50 px-2 py-0.5 text-[11px] font-medium text-slate-700 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-300">
                  {{ $pw->browser ?? 'Web Client' }}
                </span>
              </td>
              <td class="py-3 px-4">
                <code class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[11px] text-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ $pw->ip_address }}</code>
              </td>
              <td class="py-3 px-4">
                @php $appCount = $pw->user?->approvedWorkstations()->count() ?? 0; @endphp
                <span class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[11px] font-semibold {{ $appCount >= 3 ? 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-700/10 dark:bg-rose-950/60 dark:text-rose-400 dark:ring-rose-500/20' : 'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-700/10 dark:bg-blue-950/60 dark:text-blue-400 dark:ring-blue-500/20' }}">
                  {{ $appCount }} / 3 bound
                </span>
              </td>
              <td class="py-3 px-4 text-slate-500 dark:text-slate-400 text-[11px]">
                {{ $pw->updated_at->diffForHumans() }}
              </td>
              <td class="py-3 px-4 text-right">
                <div class="inline-flex items-center gap-1.5">
                  {{-- Approve Form --}}
                  <form method="POST" action="{{ route('user-security.workstations.approve', $pw) }}" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all" title="Authorize this workstation" {{ $appCount >= 3 ? 'disabled' : '' }}>
                      <i class="ph ph-check"></i>
                      Approve
                    </button>
                  </form>
                  {{-- Reject Form --}}
                  <form method="POST" action="{{ route('user-security.workstations.reject', $pw) }}" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center justify-center rounded-lg border border-rose-200 bg-white p-1.5 text-xs font-semibold text-rose-600 shadow-sm hover:bg-rose-50 dark:border-rose-900/50 dark:bg-slate-900 dark:text-rose-400 dark:hover:bg-rose-950/30 transition-all" title="Reject authorization">
                      <i class="ph ph-x"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr id="pending-empty-row">
              <td colspan="7" class="text-center py-8 text-slate-500 dark:text-slate-400">
                <div class="flex flex-col items-center justify-center">
                  <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400 mb-2">
                    <i class="ph ph-shield-check text-xl"></i>
                  </div>
                  <span class="font-medium text-xs text-slate-700 dark:text-slate-300">No pending workstation authorization requests</span>
                  <span class="text-[11px] text-slate-400 mt-0.5">All connected hospital client terminals are authorized and recognized.</span>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- Section 2: Bound Workstations Registry --}}
  <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800 overflow-hidden">
    <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
          <i class="ph-fill ph-desktop text-emerald-500"></i>
          Bound Workstations Registry
          <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-mono font-semibold text-slate-800 dark:bg-slate-800 dark:text-slate-200">{{ method_exists($boundWorkstations, 'total') ? $boundWorkstations->total() : $boundWorkstations->count() }}</span>
        </h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Authorized hospital computers locked to specific personnel accounts.</p>
      </div>
      @if($boundWorkstations->isNotEmpty())
        <button 
          type="button" 
          @click="confirmModal.openModal({
            title: 'Reset Entire Workstation Registry',
            message: 'Reset ALL workstation bindings in the system?',
            detail: 'This will wipe the entire registry. All medical and financial staff will be required to register fresh workstations on their next login.',
            targetName: 'All Hospital Devices ({{ method_exists($boundWorkstations, 'total') ? $boundWorkstations->total() : $boundWorkstations->count() }} active workstations)',
            targetIp: 'Hospital-wide',
            badgeText: 'Critical Reset',
            confirmText: 'Reset Registry',
            confirmVariant: 'danger',
            actionUrl: '{{ route('user-security.workstations.reset-all') }}',
            method: 'POST'
          })"
          class="inline-flex items-center gap-1.5 rounded-xl border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100 dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-300 dark:hover:bg-rose-950/70 transition-all shadow-sm cursor-pointer"
        >
          <i class="ph ph-trash"></i>
          Reset All Workstations
        </button>
      @endif
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse text-xs">
        <thead>
          <tr class="border-b border-slate-200/80 bg-slate-50/75 dark:border-slate-800 dark:bg-slate-950/50">
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Personnel</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Workstation Name / Hardware</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">IP Address</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Status</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Authorized By</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Last Seen</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px] text-right">Manage</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
          @forelse($boundWorkstations as $bw)
            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
              <td class="py-3 px-4">
                <div class="font-semibold text-slate-900 dark:text-white">{{ $bw->user?->name }}</div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400">{{ $bw->user?->roleLabel() }}</div>
              </td>
              <td class="py-3 px-4">
                <div class="font-medium text-slate-900 dark:text-white">{{ $bw->workstation_name }}</div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 font-mono">{{ $bw->platform }} &bull; {{ $bw->browser }}</div>
              </td>
              <td class="py-3 px-4">
                <code class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[11px] text-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ $bw->ip_address }}</code>
              </td>
              <td class="py-3 px-4">
                @if($bw->status === 'approved')
                  <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-700/10 dark:bg-emerald-950/60 dark:text-emerald-400 dark:ring-emerald-500/20">
                    <i class="ph-fill ph-check-circle text-[10px]"></i>
                    Authorized
                  </span>
                @elseif($bw->status === 'revoked')
                  <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700 ring-1 ring-inset ring-slate-700/10 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700/20">
                    <i class="ph ph-prohibit text-[10px]"></i>
                    Revoked
                  </span>
                @else
                  <span class="inline-flex items-center gap-1 rounded-md bg-rose-50 px-2 py-0.5 text-[11px] font-semibold text-rose-700 ring-1 ring-inset ring-rose-700/10 dark:bg-rose-950/60 dark:text-rose-400 dark:ring-rose-500/20">
                    <i class="ph ph-x text-[10px]"></i>
                    Rejected
                  </span>
                @endif
              </td>
              <td class="py-3 px-4 text-slate-500 dark:text-slate-400 text-[11px]">
                @if($bw->approver)
                  <div class="font-medium text-slate-700 dark:text-slate-300">{{ $bw->approver->name }}</div>
                  <div class="text-[10px] text-slate-400 font-mono">{{ $bw->approved_at?->format('M d, Y H:i') }}</div>
                @else
                  &mdash;
                @endif
              </td>
              <td class="py-3 px-4 text-slate-500 dark:text-slate-400 text-[11px]">
                {{ $bw->last_seen_at?->diffForHumans() ?? 'Never' }}
              </td>
              <td class="py-3 px-4 text-right">
                <div class="inline-flex items-center gap-1.5 justify-end">
                  @if($bw->status === 'approved')
                    <button 
                      type="button" 
                      @click="confirmModal.openModal({
                        title: 'Revoke Workstation Authorization',
                        message: 'Revoke authorization for this workstation?',
                        detail: 'Any active sessions running on this computer will be terminated immediately, and users will be signed out.',
                        targetName: '{{ addslashes($bw->workstation_name) }}',
                        targetIp: '{{ $bw->ip_address }}',
                        confirmText: 'Revoke Authorization',
                        confirmVariant: 'warning',
                        actionUrl: '{{ route('user-security.workstations.revoke', $bw) }}',
                        method: 'DELETE'
                      })"
                      class="inline-flex items-center gap-1 rounded-lg border border-amber-200 bg-white px-2.5 py-1 text-xs font-semibold text-amber-700 shadow-sm hover:bg-amber-50 dark:border-amber-900/50 dark:bg-slate-900 dark:text-amber-400 dark:hover:bg-amber-950/30 transition-all cursor-pointer" 
                      title="Revoke binding"
                    >
                      <i class="ph ph-prohibit"></i>
                      Revoke
                    </button>
                  @else
                    <form method="POST" action="{{ route('user-security.workstations.approve', $bw) }}" class="inline">
                      @csrf
                      <button type="submit" class="inline-flex items-center gap-1 rounded-lg border border-emerald-200 bg-white px-2.5 py-1 text-xs font-semibold text-emerald-600 shadow-sm hover:bg-emerald-50 dark:border-emerald-900/50 dark:bg-slate-900 dark:text-emerald-400 dark:hover:bg-emerald-950/30 transition-all" title="Re-authorize workstation">
                        <i class="ph ph-arrows-clockwise"></i>
                        Re-authorize
                      </button>
                    </form>
                  @endif

                  {{-- Permanent Delete Action Modal Trigger --}}
                  <button 
                    type="button" 
                    @click="confirmModal.openModal({
                      title: 'Permanently Remove Workstation',
                      message: 'Permanently remove this workstation record from the system?',
                      detail: 'This action is irreversible. The cryptographic device fingerprint and authorization record will be purged from the hospital registry.',
                      targetName: '{{ addslashes($bw->workstation_name) }}',
                      targetIp: '{{ $bw->ip_address }}',
                      confirmText: 'Permanently Remove',
                      confirmVariant: 'danger',
                      actionUrl: '{{ route('user-security.workstations.destroy', $bw) }}',
                      method: 'DELETE'
                    })"
                    class="inline-flex items-center justify-center rounded-lg border border-rose-200 bg-white p-1.5 text-xs font-semibold text-rose-600 shadow-sm hover:bg-rose-50 dark:border-rose-900/50 dark:bg-slate-900 dark:text-rose-400 dark:hover:bg-rose-950/30 transition-all cursor-pointer" 
                    title="Permanently delete workstation"
                  >
                    <i class="ph ph-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-10 text-slate-500 dark:text-slate-400">
                <div class="flex flex-col items-center justify-center">
                  <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400 mb-2">
                    <i class="ph ph-desktop text-xl"></i>
                  </div>
                  <span class="font-medium text-xs text-slate-700 dark:text-slate-300">No bound workstations registered</span>
                  <span class="text-[11px] text-slate-400 mt-0.5">The workstation registry is completely clean. New authorized hospital devices will appear here.</span>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <!-- Table Footer & Pagination -->
    <div class="border-t border-slate-200 p-4 dark:border-slate-800">
      @if($boundWorkstations->hasPages())
        {{ $boundWorkstations->links() }}
      @else
        <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
          <p class="font-medium">
            Showing <span class="font-mono font-bold text-slate-800 dark:text-slate-200">{{ $boundWorkstations->firstItem() ?? 0 }}</span>
            to <span class="font-mono font-bold text-slate-800 dark:text-slate-200">{{ $boundWorkstations->lastItem() ?? 0 }}</span>
            of <span class="font-mono font-bold text-slate-800 dark:text-slate-200">{{ $boundWorkstations->total() }}</span> bound workstations (5 per page)
          </p>
          <span class="text-[11px] font-mono text-slate-400">Page 1 of 1</span>
        </div>
      @endif
    </div>
  </div>

  {{-- Section 3: Active Live Sessions & Displacement Monitor --}}
  <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800 overflow-hidden">
    <div class="p-5 border-b border-slate-100 dark:border-slate-800">
      <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
        <i class="ph-fill ph-users-three text-blue-500"></i>
        Active Live Sessions (Single Active Session Enforcement)
      </h3>
      <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Real-time online sessions. Logging in from another machine terminates previous sessions automatically.</p>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse text-xs">
        <thead>
          <tr class="border-b border-slate-200/80 bg-slate-50/75 dark:border-slate-800 dark:bg-slate-950/50">
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Personnel</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Workstation</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">IP Address</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Session ID</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Login Time</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Last Heartbeat</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px] text-right">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60" id="active-sessions-tbody">
          @forelse($activeSessions as $as)
            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
              <td class="py-3 px-4">
                <div class="font-semibold text-slate-900 dark:text-white">{{ $as->user?->name }}</div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400">{{ $as->user?->roleLabel() }}</div>
              </td>
              <td class="py-3 px-4">
                <div class="font-medium text-slate-900 dark:text-white">{{ $as->device_name }}</div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 font-mono">{{ $as->workstation?->platform ?? 'Terminal' }}</div>
              </td>
              <td class="py-3 px-4">
                <code class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[11px] text-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ $as->ip_address }}</code>
              </td>
              <td class="py-3 px-4">
                <code class="font-mono text-[11px] text-slate-500 dark:text-slate-400">{{ substr($as->session_id, 0, 16) }}...</code>
              </td>
              <td class="py-3 px-4 text-slate-500 dark:text-slate-400 text-[11px] font-mono">
                {{ $as->login_at->format('M d, H:i') }}
              </td>
              <td class="py-3 px-4">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700 ring-1 ring-inset ring-emerald-700/10 dark:bg-emerald-950/60 dark:text-emerald-400 dark:ring-emerald-500/20">
                  <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                  {{ $as->last_activity_at->diffForHumans() }}
                </span>
              </td>
              <td class="py-3 px-4 text-right">
                <button 
                  type="button" 
                  @click="confirmModal.openModal({
                    title: 'Terminate Active Session',
                    message: 'Forcibly disconnect this session immediately?',
                    detail: 'The active user session will be terminated and the user will be logged out from their terminal.',
                    targetName: '{{ addslashes($as->user?->name ?? 'User') }} ({{ addslashes($as->workstation?->workstation_name ?? 'Device') }})',
                    targetIp: '{{ $as->ip_address }}',
                    confirmText: 'Disconnect Session',
                    confirmVariant: 'danger',
                    actionUrl: '{{ route('user-security.sessions.terminate', $as) }}',
                    method: 'POST'
                  })"
                  class="inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-white px-2.5 py-1 text-xs font-semibold text-rose-600 shadow-sm hover:bg-rose-50 dark:border-rose-900/50 dark:bg-slate-900 dark:text-rose-400 dark:hover:bg-rose-950/30 transition-all cursor-pointer" 
                  title="Force Disconnect"
                >
                  <i class="ph ph-sign-out"></i>
                  Disconnect
                </button>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-6 text-slate-500 dark:text-slate-400">
                No active live sessions found.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- ── Security Action Confirmation Modal (Alpine.js) ──────────────────────── --}}
  <template x-teleport="body">
  <div 
    x-show="confirmModal.open" 
    x-cloak 
    class="fixed inset-0 z-50 overflow-y-auto" 
    role="dialog" 
    aria-modal="true"
    @keydown.escape.window="confirmModal.close()"
  >
    {{-- Backdrop with blur --}}
    <div 
      x-show="confirmModal.open"
      x-transition:enter="ease-out duration-300"
      x-transition:enter-start="opacity-0"
      x-transition:enter-end="opacity-100"
      x-transition:leave="ease-in duration-200"
      x-transition:leave-start="opacity-100"
      x-transition:leave-end="opacity-0"
      class="fixed inset-0 bg-slate-950/60 backdrop-blur-md transition-opacity"
      @click="confirmModal.close()"
    ></div>

    <div class="fixed inset-0 z-10 flex min-h-full items-center justify-center p-4 pointer-events-none">
      <div 
        x-show="confirmModal.open"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
        @click.outside="confirmModal.close()"
        class="pointer-events-auto w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800 text-left transition-all"
      >
        {{-- Icon & Header --}}
        <div class="flex items-start gap-3.5">
          <div 
            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl shadow-xs"
            :class="confirmModal.confirmVariant === 'warning' 
              ? 'bg-amber-50 text-amber-600 ring-1 ring-amber-500/20 dark:bg-amber-950/40 dark:text-amber-400' 
              : 'bg-rose-50 text-rose-600 ring-1 ring-rose-500/20 dark:bg-rose-950/40 dark:text-rose-400'"
          >
            <i class="ph-bold text-xl" :class="confirmModal.confirmVariant === 'warning' ? 'ph-warning' : 'ph-trash'"></i>
          </div>

          <div class="flex-1 min-w-0 pt-0.5">
            <h3 class="text-base font-bold text-slate-900 dark:text-white leading-tight" x-text="confirmModal.title"></h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-normal leading-relaxed" x-text="confirmModal.message"></p>
          </div>
        </div>

        {{-- Workstation Target Info Card (if device specified) --}}
        <template x-if="confirmModal.targetName">
          <div class="mt-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 p-3 ring-1 ring-slate-200/80 dark:ring-slate-700/60 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2.5 min-w-0">
              <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white dark:bg-slate-700 text-slate-500 dark:text-slate-300 ring-1 ring-slate-200 dark:ring-slate-600">
                <i class="ph-bold ph-desktop text-sm"></i>
              </div>
              <div class="min-w-0 truncate">
                <p class="text-xs font-semibold text-slate-900 dark:text-white truncate" x-text="confirmModal.targetName"></p>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 font-mono truncate" x-text="confirmModal.targetIp ? ('IP: ' + confirmModal.targetIp) : ''"></p>
              </div>
            </div>
            <span 
              class="shrink-0 text-[10px] uppercase font-bold tracking-wider rounded-md px-2 py-0.5"
              :class="confirmModal.confirmVariant === 'warning'
                ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300'
                : 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300'"
              x-text="confirmModal.badgeText"
            ></span>
          </div>
        </template>

        {{-- Details / Consequences Text --}}
        <template x-if="confirmModal.detail">
          <p class="mt-3 text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed bg-amber-50/50 dark:bg-amber-950/20 border border-amber-200/60 dark:border-amber-900/40 rounded-xl p-2.5" x-text="confirmModal.detail"></p>
        </template>

        {{-- Hidden Form for Action Execution --}}
        <form x-ref="confirmForm" :action="confirmModal.actionUrl" method="POST" class="hidden">
          @csrf
          <input type="hidden" name="_method" :value="confirmModal.method">
        </form>

        {{-- Action Buttons --}}
        <div class="mt-5 flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100 dark:border-slate-800">
          <button 
            type="button" 
            @click="confirmModal.close()" 
            :disabled="confirmModal.isSubmitting"
            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 transition-all cursor-pointer disabled:opacity-50"
          >
            Cancel
          </button>

          <button 
            type="button" 
            @click="confirmModal.isSubmitting = true; $refs.confirmForm.submit()" 
            :disabled="confirmModal.isSubmitting"
            class="inline-flex items-center gap-1.5 rounded-xl px-4 py-2 text-xs font-semibold text-white shadow-sm transition-all cursor-pointer disabled:opacity-75"
            :class="confirmModal.confirmVariant === 'warning'
              ? 'bg-amber-600 hover:bg-amber-700 focus:ring-2 focus:ring-amber-500/20'
              : 'bg-rose-600 hover:bg-rose-700 focus:ring-2 focus:ring-rose-500/20'"
          >
            <template x-if="confirmModal.isSubmitting">
              <i class="ph-bold ph-spinner animate-spin text-sm"></i>
            </template>
            <template x-if="!confirmModal.isSubmitting">
              <i class="ph-bold text-sm" :class="confirmModal.confirmVariant === 'warning' ? 'ph-prohibit' : 'ph-trash'"></i>
            </template>
            <span x-text="confirmModal.isSubmitting ? 'Processing...' : confirmModal.confirmText"></span>
          </button>
        </div>
      </div>
    </div>
  </div>
  </template>

</div>

{{-- Real-Time Polling Script for Super Admin Panel --}}
<script>
  const pollUrl = '{{ route('user-security.workstations.poll') }}';
  const badgePending = document.getElementById('badge-pending-count');
  const statPending  = document.getElementById('stat-pending-count');
  const statActive   = document.getElementById('stat-active-count');

  let previousPendingCount = {{ $pendingWorkstations->count() }};

  async function pollSuperAdminSecurity() {
    try {
      const response = await fetch(pollUrl, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        }
      });

      if (!response.ok) return;

      const data = await response.json();

      if (statPending)  statPending.textContent = data.pending_count;
      if (badgePending) badgePending.textContent = data.pending_count;
      if (statActive)   statActive.textContent = data.active_count;

      // If pending count changed, reload the page to refresh pending requests seamlessly
      if (data.pending_count !== previousPendingCount) {
        previousPendingCount = data.pending_count;
        window.location.reload();
      }
    } catch (_) {}
  }

  setInterval(pollSuperAdminSecurity, 3500);
</script>
@endsection

@extends('layouts.app')

@section('title', 'User Accounts — User & Security Management | FMS')
@section('module', 'user-security')
@section('page', 'users')

@section('content')
<div class="space-y-6" x-data="{
  init() {
    this.$watch('editModal.open', () => this.syncBodyLock());
    this.$watch('confirmModal.open', () => this.syncBodyLock());
  },
  syncBodyLock() {
    if (this.editModal.open || this.confirmModal.open) {
      document.body.classList.add('overflow-hidden', 'modal-open');
    } else {
      document.body.classList.remove('overflow-hidden', 'modal-open');
    }
  },
  // Edit User Modal State (Photo 1)
  editModal: {
    open: false,
    id: null,
    name: '',
    email: '',
    role: 'StaffAccountant',
    roleLabel: '',
    initials: '',
    updateUrl: '',
    isSubmitting: false,
    openModal(data) {
      this.id = data.id;
      this.name = data.name;
      this.email = data.email;
      this.role = data.role;
      this.roleLabel = data.roleLabel;
      this.initials = data.initials;
      this.updateUrl = data.updateUrl;
      this.isSubmitting = false;
      this.open = true;
    },
    close() {
      if (!this.isSubmitting) {
        this.open = false;
      }
    }
  },

  // Confirmation Modal State (Photos 2 & 3: Reset Password & Suspend/Activate)
  confirmModal: {
    open: false,
    title: '',
    message: '',
    detail: '',
    targetName: '',
    targetBadge: '',
    confirmText: 'Confirm',
    confirmVariant: 'danger',
    icon: 'ph-key',
    actionUrl: '',
    method: 'POST',
    isSubmitting: false,
    openModal(config) {
      this.title = config.title || 'Confirm Action';
      this.message = config.message || 'Are you sure?';
      this.detail = config.detail || '';
      this.targetName = config.targetName || '';
      this.targetBadge = config.targetBadge || '';
      this.confirmText = config.confirmText || 'Confirm';
      this.confirmVariant = config.confirmVariant || 'danger';
      this.icon = config.icon || 'ph-warning';
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
  },

  // Helper to trigger Suspend/Activate Confirmation Modal cleanly
  confirmToggleUser(id, name, isActive, roleLabel, actionUrl) {
    this.confirmModal.openModal({
      title: isActive ? 'Suspend User Account' : 'Activate User Account',
      message: (isActive ? 'Suspend account for ' : 'Activate account for ') + name + '?',
      detail: isActive 
        ? 'Access for this user account will be blocked immediately and all active sessions will be terminated.' 
        : 'System access will be restored and this user will be permitted to authenticate into authorized terminals.',
      targetName: name,
      targetBadge: 'ID #' + id + ' &bull; ' + roleLabel,
      confirmText: isActive ? 'Suspend Account' : 'Activate Account',
      confirmVariant: isActive ? 'danger' : 'emerald',
      icon: isActive ? 'ph-lock' : 'ph-lock-open',
      actionUrl: actionUrl,
      method: 'POST'
    });
  },

  // Helper to trigger Reset Password Confirmation Modal cleanly
  confirmResetPassword(id, name, roleLabel, actionUrl) {
    this.confirmModal.openModal({
      title: 'Reset User Password',
      message: 'Reset password for ' + name + '? They will be required to change it on next login.',
      detail: 'A secure temporary password (Hospital@{{ now()->year }}) will be assigned. The user will be required to configure a new credential upon their next login.',
      targetName: name,
      targetBadge: 'ID #' + id + ' &bull; ' + roleLabel,
      confirmText: 'Reset Password',
      confirmVariant: 'warning',
      icon: 'ph-key',
      actionUrl: actionUrl,
      method: 'POST'
    });
  }
}">
  <!-- Executive Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
    <div>
      <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
          Personnel &amp; User Accounts
        </h1>
    </div>

    <div class="flex items-center gap-2.5">
      <a 
        href="{{ route('user-security.audit-trail') }}" 
        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition-all dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 cursor-pointer"
      >
        <i class="ph-bold ph-clock-countdown text-emerald-600"></i>
        <span>Audit Trail</span>
      </a>
      <a 
        href="{{ route('user-security.users.create') }}" 
        id="btn-add-user"
        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 ring-1 ring-emerald-600/20 transition-all cursor-pointer"
      >
        <i class="ph-bold ph-user-plus"></i>
        <span>Add Personnel User</span>
      </a>
    </div>
  </div>

  <!-- Session Alerts -->
  @if(session('success'))
    <div class="rounded-xl bg-emerald-50 p-4 text-xs font-semibold text-emerald-800 ring-1 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <i class="ph-bold ph-check-circle text-lg text-emerald-600"></i>
        <span>{!! session('success') !!}</span>
      </div>
    </div>
  @endif

  @if(session('error'))
    <div class="rounded-xl bg-rose-50 p-4 text-xs font-semibold text-rose-800 ring-1 ring-rose-600/20 dark:bg-rose-950/40 dark:text-rose-300 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <i class="ph-bold ph-warning-circle text-lg text-rose-600"></i>
        <span>{{ session('error') }}</span>
      </div>
    </div>
  @endif

  <!-- Metric Summary Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
    <x-stat-card 
      title="Total Accounts" 
      :value="$metrics['total'] ?? (method_exists($users, 'total') ? $users->total() : $users->count())" 
      :isCurrency="false"
      icon="ph-users" 
      color="blue" 
      subtitle="Registered hospital personnel"
    />
    <x-stat-card 
      title="Active Accounts" 
      :value="$metrics['active'] ?? 0" 
      :isCurrency="false"
      icon="ph-check-circle" 
      color="emerald" 
      subtitle="Authorized for system login"
    />
    <x-stat-card 
      title="Suspended Personnel" 
      :value="$metrics['suspended'] ?? 0" 
      :isCurrency="false"
      icon="ph-lock" 
      color="rose" 
      subtitle="Access temporarily locked"
    />
    <x-stat-card 
      title="Pending Password Reset" 
      :value="$metrics['pending_reset'] ?? 0" 
      :isCurrency="false"
      icon="ph-key" 
      color="amber" 
      subtitle="Forced reset on next login"
    />
  </div>


  <!-- User Table -->
  <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
    <!-- Filter Toolbar -->
    <div class="border-b border-slate-200 p-4 dark:border-slate-800">
      <form method="GET" action="{{ route('user-security.users') }}" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 flex-wrap">
        <div class="flex items-center gap-2.5 flex-wrap">
          <div class="flex items-center gap-1.5 text-xs text-slate-500">
            <i class="ph-bold ph-funnel"></i>
            <span>Role:</span>
          </div>
          <select 
            name="role" 
            onchange="this.form.submit()" 
            class="rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-800 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 focus:outline-none dark:bg-slate-800 dark:text-white dark:ring-slate-700"
          >
            <option value="">All Roles</option>
            <option value="CFO" @selected(($role ?? '') === 'CFO')>CFO</option>
            <option value="FinanceManager" @selected(($role ?? '') === 'FinanceManager')>Finance Manager</option>
            <option value="StaffAccountant" @selected(($role ?? '') === 'StaffAccountant')>Staff Accountant</option>
            <option value="BillingClerk" @selected(($role ?? '') === 'BillingClerk')>Billing Clerk</option>
            <option value="Cashier" @selected(($role ?? '') === 'Cashier')>Cashier</option>
            <option value="Auditor" @selected(($role ?? '') === 'Auditor')>Auditor</option>
          </select>

          <div class="flex items-center gap-1.5 text-xs text-slate-500 ml-2">
            <span>Status:</span>
          </div>
          <select 
            name="status" 
            onchange="this.form.submit()" 
            class="rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-800 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 focus:outline-none dark:bg-slate-800 dark:text-white dark:ring-slate-700"
          >
            <option value="">All Statuses</option>
            <option value="active" @selected(($status ?? '') === 'active')>Active</option>
            <option value="suspended" @selected(($status ?? '') === 'suspended')>Suspended</option>
          </select>
        </div>

        <div class="flex items-center gap-2">
          <div class="relative w-full sm:w-72">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
              <i class="ph ph-magnifying-glass text-sm"></i>
            </div>
            <input 
              type="search" 
              name="search" 
              value="{{ $search ?? '' }}" 
              placeholder="Search personnel name, email..." 
              class="w-full rounded-xl border-0 bg-slate-50 py-1.5 pl-9 pr-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-emerald-600 focus:outline-none dark:bg-slate-800 dark:text-white dark:ring-slate-700"
            >
          </div>
          <button type="submit" class="rounded-xl bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 transition-colors">
            Search
          </button>
          @if(!empty($search) || !empty($role) || !empty($status))
            <a href="{{ route('user-security.users') }}" class="rounded-xl px-2 py-1.5 text-xs font-semibold text-slate-500 hover:text-slate-700 dark:text-slate-300">
              Reset
            </a>
          @endif
        </div>
      </form>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="border-b border-slate-200 bg-slate-50/75 dark:border-slate-800 dark:bg-slate-800/60 sticky top-0">
          <tr>
            <th class="py-3 px-4 font-semibold text-slate-700 dark:text-slate-300">Personnel User</th>
            <th class="py-3 px-4 font-semibold text-slate-700 dark:text-slate-300">Role / Access</th>
            <th class="py-3 px-4 font-semibold text-slate-700 dark:text-slate-300 text-center">Status</th>
            <th class="py-3 px-4 font-semibold text-slate-700 dark:text-slate-300">Last Login</th>
            <th class="py-3 px-4 font-semibold text-slate-700 dark:text-slate-300">Last Known IP</th>
            <th class="py-3 px-4 font-semibold text-slate-700 dark:text-slate-300 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
          @forelse($users as $user)
          <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
            <td class="py-3.5 px-4">
              <div class="flex items-center gap-3">
                <div 
                  class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl font-bold text-white text-xs shadow-sm"
                  style="background: {{ $user->role === 'CFO' ? '#7c3aed' : ($user->role === 'Auditor' ? '#db2777' : ($user->role === 'FinanceManager' ? '#2563eb' : '#059669')) }};"
                >
                  {{ strtoupper(substr($user->name, 0, 2)) }}
                </div>
                <div>
                  <div class="font-bold text-slate-900 dark:text-white">{{ $user->name }}</div>
                  <div class="font-mono text-xs text-slate-400">{{ $user->email }}</div>
                  @if($user->must_change_password)
                    <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-amber-600 dark:text-amber-400 mt-0.5">
                      <i class="ph-bold ph-warning"></i>
                      <span>Must change password</span>
                    </span>
                  @endif
                </div>
              </div>
            </td>
            <td class="py-3.5 px-4">
              @php
                $roleBadge = match($user->role) {
                  'CFO' => 'bg-purple-50 text-purple-700 dark:bg-purple-950/40 dark:text-purple-300 border-purple-200 dark:border-purple-800',
                  'FinanceManager' => 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300 border-blue-200 dark:border-blue-800',
                  'StaffAccountant' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
                  'BillingClerk' => 'bg-cyan-50 text-cyan-700 dark:bg-cyan-950/40 dark:text-cyan-300 border-cyan-200 dark:border-cyan-800',
                  'Cashier' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300 border-amber-200 dark:border-amber-800',
                  'Auditor' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 border-rose-200 dark:border-rose-800',
                  default => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700',
                };
              @endphp
              <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold border {{ $roleBadge }}">
                {{ $user->roleLabel() }}
              </span>
            </td>
            <td class="py-3.5 px-4 text-center">
              @if($user->isActive())
                <x-status-badge status="Active" color="emerald" />
              @else
                <x-status-badge status="Suspended" color="rose" />
              @endif
            </td>
            <td class="py-3.5 px-4 text-slate-500 dark:text-slate-400">
              {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : '—' }}
            </td>
            <td class="py-3.5 px-4 font-mono text-slate-500 dark:text-slate-400">
              {{ $user->last_login_ip ?? '—' }}
            </td>
            <td class="py-3.5 px-4 text-right">
              <div class="inline-flex items-center justify-end gap-1.5">
                {{-- Edit User Button: Opens Modal instead of navigating away (Photo 1) --}}
                <button 
                  type="button"
                  @click="editModal.openModal({
                    id: {{ $user->id }},
                    name: '{{ addslashes($user->name) }}',
                    email: '{{ addslashes($user->email) }}',
                    role: '{{ $user->role }}',
                    roleLabel: '{{ addslashes($user->roleLabel()) }}',
                    initials: '{{ strtoupper(substr($user->name, 0, 2)) }}',
                    updateUrl: '{{ route('user-security.users.update', $user) }}'
                  })"
                  class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 hover:text-emerald-600 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800 cursor-pointer transition-colors" 
                  title="Edit user profile"
                >
                  <i class="ph ph-pencil text-sm"></i>
                </button>

                {{-- Reset Password Button: Opens Confirmation Modal (Photo 2) --}}
                <button 
                  type="button" 
                  @click="confirmResetPassword({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ addslashes($user->roleLabel()) }}', '{{ route('user-security.users.reset-password', $user) }}')"
                  class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-amber-600 hover:bg-amber-50 hover:border-amber-200 dark:border-slate-700 dark:hover:bg-amber-950/30 cursor-pointer transition-colors" 
                  title="Reset password"
                >
                  <i class="ph ph-key text-sm"></i>
                </button>

                {{-- Suspend / Activate Account Button: Opens Confirmation Modal (Photo 3) --}}
                @if($user->id !== auth()->id())
                <button 
                  type="button" 
                  @click="confirmToggleUser({{ $user->id }}, '{{ addslashes($user->name) }}', {{ $user->isActive() ? 'true' : 'false' }}, '{{ addslashes($user->roleLabel()) }}', '{{ route('user-security.users.toggle-status', $user) }}')"
                  class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 {{ $user->isActive() ? 'text-rose-600 hover:bg-rose-50 hover:border-rose-200 dark:hover:bg-rose-950/30' : 'text-emerald-600 hover:bg-emerald-50 hover:border-emerald-200 dark:hover:bg-emerald-950/30' }} dark:border-slate-700 cursor-pointer transition-colors"
                  title="{{ $user->isActive() ? 'Suspend' : 'Activate' }} account"
                >
                  <i class="ph {{ $user->isActive() ? 'ph-lock' : 'ph-lock-open' }} text-sm"></i>
                </button>
                @endif
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="6" class="py-12 text-center text-slate-400 dark:text-slate-500">
              <i class="ph ph-users text-3xl block mb-2 text-slate-300 dark:text-slate-600"></i>
              No users found.
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <!-- Table Footer & Pagination -->
    <div class="border-t border-slate-200 p-4 dark:border-slate-800">
      @if($users->hasPages())
        {{ $users->links() }}
      @else
        <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
          <p class="font-medium">
            Showing <span class="font-mono font-bold text-slate-800 dark:text-slate-200">{{ $users->firstItem() ?? 0 }}</span>
            to <span class="font-mono font-bold text-slate-800 dark:text-slate-200">{{ $users->lastItem() ?? 0 }}</span>
            of <span class="font-mono font-bold text-slate-800 dark:text-slate-200">{{ $users->total() }}</span> records (5 per page)
          </p>
          <span class="text-[11px] font-mono text-slate-400">Page 1 of 1</span>
        </div>
      @endif
    </div>
  </div>

  {{-- ── 1. Edit User Modal (Alpine.js — Photo 1) ────────────────────────────────────────── --}}
  <template x-teleport="body">
    <div 
      x-show="editModal.open" 
      x-cloak 
      class="fixed inset-0 z-50 overflow-y-auto" 
      role="dialog" 
      aria-modal="true"
      @keydown.escape.window="editModal.close()"
    >
      {{-- Backdrop --}}
      <div 
        x-show="editModal.open"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-slate-950/60 backdrop-blur-md transition-opacity"
        @click="editModal.close()"
      ></div>

      <div class="fixed inset-0 z-10 flex min-h-full items-center justify-center p-4 pointer-events-none">
        <div 
          x-show="editModal.open"
          x-transition:enter="ease-out duration-300"
          x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
          x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
          x-transition:leave="ease-in duration-200"
          x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
          x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
          @click.outside="editModal.close()"
          class="pointer-events-auto w-full max-w-lg transform overflow-hidden rounded-2xl bg-white p-6 sm:p-7 shadow-2xl ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800 text-left transition-all"
        >
        {{-- Header --}}
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
          <div class="flex items-center gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-500/20 dark:bg-emerald-950/40 dark:text-emerald-400">
              <i class="ph-bold ph-user-gear text-xl"></i>
            </span>
            <div>
              <h3 class="text-base font-bold text-slate-900 dark:text-white leading-tight">Edit User Profile</h3>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Modify personnel details and security access role.</p>
            </div>
          </div>
          <button 
            type="button" 
            @click="editModal.close()" 
            class="rounded-xl p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-200 transition-colors cursor-pointer"
          >
            <i class="ph-bold ph-x text-lg"></i>
          </button>
        </div>

        {{-- Current Info Badge Card (Photo 1) --}}
        <div class="mt-4 flex items-center gap-3.5 rounded-xl border border-slate-100 bg-slate-50/80 p-3.5 dark:border-slate-800 dark:bg-slate-950/60">
          <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-600 font-bold text-white shadow-sm text-sm" x-text="editModal.initials">
          </div>
          <div class="min-w-0 flex-1">
            <div class="text-sm font-bold text-slate-900 dark:text-white truncate" x-text="editModal.name"></div>
            <div class="text-xs font-mono text-slate-500 dark:text-slate-400 truncate" x-text="editModal.email"></div>
            <div class="mt-1 flex items-center gap-2">
              <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-700/10 dark:bg-emerald-950/60 dark:text-emerald-300 dark:ring-emerald-500/20">
                <i class="ph-bold ph-shield-check text-[10px]"></i>
                <span x-text="editModal.roleLabel"></span>
              </span>
              <span class="text-[10px] text-slate-400 font-mono" x-text="'ID #' + editModal.id"></span>
            </div>
          </div>
        </div>

        {{-- Form --}}
        <form :action="editModal.updateUrl" method="POST" @submit="editModal.isSubmitting = true" class="mt-4 space-y-4">
          @csrf
          @method('PATCH')

          {{-- Full Name --}}
          <div>
            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
              FULL NAME <span class="text-rose-500">*</span>
            </label>
            <div class="relative">
              <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                <i class="ph-bold ph-user text-sm"></i>
              </div>
              <input 
                type="text" 
                name="name" 
                x-model="editModal.name" 
                required 
                class="block w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-3 text-xs text-slate-900 placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-800 dark:bg-slate-950 dark:text-white dark:placeholder:text-slate-600 transition-all shadow-xs"
              >
            </div>
          </div>

          {{-- Hospital Email --}}
          <div>
            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
              HOSPITAL EMAIL ADDRESS <span class="text-rose-500">*</span>
            </label>
            <div class="relative">
              <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                <i class="ph-bold ph-envelope-simple text-sm"></i>
              </div>
              <input 
                type="email" 
                name="email" 
                x-model="editModal.email" 
                required 
                class="block w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-3 text-xs font-mono text-slate-900 placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-800 dark:bg-slate-950 dark:text-white dark:placeholder:text-slate-600 transition-all shadow-xs"
              >
            </div>
          </div>

          {{-- Assigned Role --}}
          <div>
            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
              ASSIGNED SYSTEM ROLE <span class="text-rose-500">*</span>
            </label>
            <div class="relative">
              <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                <i class="ph-bold ph-shield-star text-sm"></i>
              </div>
              <select 
                name="role" 
                x-model="editModal.role" 
                required 
                class="block w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-8 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-800 dark:bg-slate-950 dark:text-white transition-all shadow-xs"
              >
                <option value="FinanceManager">Finance Manager</option>
                <option value="StaffAccountant">Staff Accountant</option>
                <option value="BillingClerk">Billing Clerk</option>
                <option value="Cashier">Cashier</option>
                <option value="Auditor">Internal Auditor</option>
                <option value="CFO">CFO (Executive Administrator)</option>
              </select>
            </div>
          </div>

          {{-- Actions --}}
          <div class="mt-5 flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100 dark:border-slate-800">
            <button 
              type="button" 
              @click="editModal.close()" 
              :disabled="editModal.isSubmitting"
              class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 transition-all cursor-pointer disabled:opacity-50"
            >
              Cancel
            </button>
            <button 
              type="submit" 
              :disabled="editModal.isSubmitting"
              class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-500/20 transition-all cursor-pointer disabled:opacity-75"
            >
              <template x-if="editModal.isSubmitting">
                <i class="ph-bold ph-spinner animate-spin text-sm"></i>
              </template>
              <template x-if="!editModal.isSubmitting">
                <i class="ph-bold ph-floppy-disk text-sm"></i>
              </template>
              <span x-text="editModal.isSubmitting ? 'Saving...' : 'Save Changes'"></span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
  </template>

  {{-- ── 2. User Action Confirmation Modal (Alpine.js — Photos 2 & 3) ──────────────────────── --}}
  <template x-teleport="body">
  <div 
    x-show="confirmModal.open" 
    x-cloak 
    class="fixed inset-0 z-50 overflow-y-auto" 
    role="dialog" 
    aria-modal="true"
    @keydown.escape.window="confirmModal.close()"
  >
    {{-- Backdrop --}}
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
        {{-- Icon & Title --}}
        <div class="flex items-start gap-3.5">
          <div 
            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl shadow-xs"
            :class="{
              'bg-amber-50 text-amber-600 ring-1 ring-amber-500/20 dark:bg-amber-950/40 dark:text-amber-400': confirmModal.confirmVariant === 'warning',
              'bg-rose-50 text-rose-600 ring-1 ring-rose-500/20 dark:bg-rose-950/40 dark:text-rose-400': confirmModal.confirmVariant === 'danger',
              'bg-emerald-50 text-emerald-600 ring-1 ring-emerald-500/20 dark:bg-emerald-950/40 dark:text-emerald-400': confirmModal.confirmVariant === 'emerald'
            }"
          >
            <i class="ph-bold text-xl" :class="confirmModal.icon"></i>
          </div>

          <div class="flex-1 min-w-0 pt-0.5">
            <h3 class="text-base font-bold text-slate-900 dark:text-white leading-tight" x-text="confirmModal.title"></h3>
            <p class="text-xs text-slate-600 dark:text-slate-300 mt-1 font-normal leading-relaxed" x-text="confirmModal.message"></p>
          </div>
        </div>

        {{-- Target User Preview Card --}}
        <template x-if="confirmModal.targetName">
          <div class="mt-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 p-3 ring-1 ring-slate-200/80 dark:ring-slate-700/60 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2.5 min-w-0">
              <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white dark:bg-slate-700 text-slate-500 dark:text-slate-300 ring-1 ring-slate-200 dark:ring-slate-600">
                <i class="ph-bold ph-user text-sm"></i>
              </div>
              <div class="min-w-0 truncate">
                <p class="text-xs font-semibold text-slate-900 dark:text-white truncate" x-text="confirmModal.targetName"></p>
                <p class="text-[10px] text-slate-400 font-mono truncate" x-html="confirmModal.targetBadge"></p>
              </div>
            </div>
            <span 
              class="shrink-0 text-[10px] uppercase font-bold tracking-wider rounded-md px-2 py-0.5"
              :class="{
                'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300': confirmModal.confirmVariant === 'warning',
                'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300': confirmModal.confirmVariant === 'danger',
                'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300': confirmModal.confirmVariant === 'emerald'
              }"
              x-text="confirmModal.confirmVariant === 'danger' ? 'Suspension' : (confirmModal.confirmVariant === 'warning' ? 'Password Reset' : 'Activation')"
            ></span>
          </div>
        </template>

        {{-- Detail note --}}
        <template x-if="confirmModal.detail">
          <p class="mt-3 text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed bg-slate-50 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-700/50 rounded-xl p-2.5" x-text="confirmModal.detail"></p>
        </template>

        {{-- Hidden Form for Action Execution --}}
        <form x-ref="userConfirmForm" :action="confirmModal.actionUrl" method="POST" class="hidden">
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
            @click="confirmModal.isSubmitting = true; $refs.userConfirmForm.submit()" 
            :disabled="confirmModal.isSubmitting"
            class="inline-flex items-center gap-1.5 rounded-xl px-4 py-2 text-xs font-semibold text-white shadow-sm transition-all cursor-pointer disabled:opacity-75"
            :class="{
              'bg-amber-600 hover:bg-amber-700 focus:ring-2 focus:ring-amber-500/20': confirmModal.confirmVariant === 'warning',
              'bg-rose-600 hover:bg-rose-700 focus:ring-2 focus:ring-rose-500/20': confirmModal.confirmVariant === 'danger',
              'bg-emerald-600 hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-500/20': confirmModal.confirmVariant === 'emerald'
            }"
          >
            <template x-if="confirmModal.isSubmitting">
              <i class="ph-bold ph-spinner animate-spin text-sm"></i>
            </template>
            <template x-if="!confirmModal.isSubmitting">
              <i class="ph-bold text-sm" :class="confirmModal.icon"></i>
            </template>
            <span x-text="confirmModal.isSubmitting ? 'Processing...' : confirmModal.confirmText"></span>
          </button>
        </div>
      </div>
    </div>
  </div>
  </template>

</div>
@endsection


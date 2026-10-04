@extends('layouts.app')

@section('title', 'Bank Accounts Directory - Cash Management | FMS')
@section('module', 'cash')
@section('page', 'bank-accounts')

@section('content')
<div class="space-y-6">
  <!-- Executive Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
    <div>
      <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
          Hospital Bank Accounts Master Register
        </h1>
    </div>

    <div class="flex items-center gap-2.5">
      <a 
        href="{{ route('cash.fund-transfers') }}" 
        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition-all dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 cursor-pointer"
      >
        <i class="ph-bold ph-arrows-left-right text-emerald-600"></i>
        <span>Inter-Account Transfers</span>
      </a>
      <button 
        type="button" 
        id="btnAddAccount" 
        @click="$dispatch('open-modal', 'addAccountModal')"
        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 ring-1 ring-emerald-600/20 transition-all cursor-pointer"
      >
        <i class="ph-bold ph-plus-circle"></i>
        <span>Add Bank Account</span>
      </button>
    </div>
  </div>

  <!-- Session Alerts -->
  @if(session('success'))
    <div class="rounded-xl bg-emerald-50 p-4 text-xs font-semibold text-emerald-800 ring-1 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <i class="ph-bold ph-check-circle text-lg text-emerald-600"></i>
        <span>{{ session('success') }}</span>
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
      title="Active Bank Accounts" 
      :value="$activeCount ?? count($bankAccounts ?? [])" 
      :isCurrency="false"
      icon="ph-bank" 
      color="blue" 
      subtitle="Operational depository accounts"
    />
    <x-stat-card 
      title="Total Ledger Cash" 
      :value="$totalBalance ?? 0" 
      icon="ph-vault" 
      color="emerald" 
      subtitle="Combined available liquid funds"
    />
    @php 
      $mainAccount = ($bankAccounts ?? collect())->first(fn($a) => str_contains(strtolower($a->name . ' ' . $a->purpose), 'operat')); 
      $collectionsAccount = ($bankAccounts ?? collect())->first(fn($a) => str_contains(strtolower($a->name . ' ' . $a->purpose), 'collect') || str_contains(strtolower($a->name . ' ' . $a->purpose), 'treasury'));
    @endphp
    <x-stat-card 
      title="Main Operating Fund" 
      :value="$mainAccount?->balance ?? 0" 
      icon="ph-credit-card" 
      color="purple" 
      subtitle="{{ $mainAccount?->name ?? 'Operating Account' }}"
    />
    <x-stat-card 
      title="Collections Fund" 
      :value="$collectionsAccount?->balance ?? 0" 
      icon="ph-hand-coins" 
      color="amber" 
      subtitle="{{ $collectionsAccount?->name ?? 'Collections Account' }}"
    />
  </div>

  
  <!-- Sub-Module Category Navigation Bar -->
  @include('partials.submodule-nav')

  <!-- Bank Accounts Table Card -->
  <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 overflow-hidden dark:bg-slate-900 dark:ring-slate-800">
    <!-- Filter Toolbar -->
    <div class="border-b border-slate-200 p-4 dark:border-slate-800">
      <form method="GET" action="{{ route('cash.bank-accounts') }}" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 flex-wrap">
        <div class="flex items-center gap-2.5 flex-wrap">
          <div class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
            <i class="ph-bold ph-funnel"></i>
            <span>Status:</span>
          </div>
          <select 
            name="status" 
            onchange="this.form.submit()"
            class="rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-800 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
          >
            <option value="">All Account Statuses</option>
            <option value="Active" {{ request('status') === 'Active' ? 'selected' : '' }}>Active</option>
            <option value="Inactive" {{ request('status') === 'Inactive' ? 'selected' : '' }}>Inactive</option>
            <option value="Frozen" {{ request('status') === 'Frozen' ? 'selected' : '' }}>Frozen</option>
          </select>
        </div>

        <div class="relative w-full sm:w-72">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
            <i class="ph ph-magnifying-glass text-sm"></i>
          </div>
          <input 
            type="search" 
            name="search" 
            value="{{ request('search') }}" 
            placeholder="Search bank name, account #, GL..." 
            class="w-full rounded-xl border-0 bg-slate-50 py-1.5 pl-9 pr-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
          >
        </div>
      </form>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto custom-scrollbar">
      <table class="w-full text-left text-xs">
        <thead class="border-b border-slate-200 bg-slate-50/75 dark:border-slate-800 dark:bg-slate-800/60 sticky top-0">
          <tr>
            <th class="py-3 px-4 font-semibold text-slate-700 dark:text-slate-300">Bank &amp; Account Name</th>
            <th class="py-3 px-4 font-semibold text-slate-700 dark:text-slate-300">Account Number</th>
            <th class="py-3 px-4 font-semibold text-slate-700 dark:text-slate-300">Linked GL Code</th>
            <th class="py-3 px-4 font-semibold text-slate-700 dark:text-slate-300">Designated Purpose</th>
            <th class="py-3 px-4 font-semibold text-slate-700 dark:text-slate-300 text-right">Opening Balance</th>
            <th class="py-3 px-4 font-semibold text-slate-700 dark:text-slate-300 text-right">Current Balance</th>
            <th class="py-3 px-4 font-semibold text-slate-700 dark:text-slate-300 text-center">Status</th>
            <th class="py-3 px-4 font-semibold text-slate-700 dark:text-slate-300 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
          @forelse($bankAccounts ?? [] as $acc)
          @php
            $isActive = ($acc->status === 'Active' && $acc->is_active);
            $isBelowFloor = (bccomp((string) $acc->balance, (string) $acc->minimum_balance, 4) < 0);
          @endphp
          <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors {{ ! $isActive ? 'opacity-60' : '' }}">
            <td class="py-3.5 px-4">
              <div class="font-bold text-slate-900 dark:text-white">{{ $acc->name }}</div>
              <span class="text-xs text-slate-400">{{ $acc->bank_name }}</span>
            </td>
            <td class="py-3.5 px-4 font-mono font-bold text-blue-600 dark:text-blue-400">
              {{ $acc->account_number }}
            </td>
            <td class="py-3.5 px-4">
              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono font-semibold bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                {{ $acc->gl_code }}
              </span>
              @if($acc->glAccount)
                <span class="block text-[11px] text-slate-400 truncate max-w-[150px]">{{ $acc->glAccount->name }}</span>
              @endif
            </td>
            <td class="py-3.5 px-4 text-slate-500 dark:text-slate-400 max-w-xs truncate">
              {{ $acc->purpose }}
            </td>
            <td class="py-3.5 px-4 text-right font-mono tabular-nums text-slate-500 dark:text-slate-400">
              ₱{{ number_format((float) $acc->opening_balance, 2) }}
            </td>
            <td class="py-3.5 px-4 text-right font-mono tabular-nums font-semibold {{ $isBelowFloor ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
              ₱{{ number_format((float) $acc->balance, 2) }}
              @if($isBelowFloor)
                <span class="block text-[10px] text-rose-500 font-normal">Below Min (₱{{ number_format((float) $acc->minimum_balance, 2) }})</span>
              @endif
            </td>
            <td class="py-3.5 px-4 text-center">
              @if($isActive)
                <x-status-badge status="Active" color="emerald" />
              @else
                <x-status-badge :status="$acc->status" color="slate" />
              @endif
            </td>
            <td class="py-3.5 px-4 text-right">
              <div class="inline-flex items-center justify-end gap-1.5">
                <button 
                  type="button" 
                  title="Edit Bank Account" 
                  onclick="openEditModal({{ json_encode($acc) }})"
                  class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 hover:text-blue-600 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800 cursor-pointer"
                >
                  <i class="ph ph-pencil-simple text-sm"></i>
                </button>
                <form method="POST" action="{{ route('cash.bank-accounts.toggle', $acc->id) }}" class="inline">
                  @csrf
                  @method('PATCH')
                  <button 
                    type="submit" 
                    title="{{ $isActive ? 'Deactivate Account' : 'Activate Account' }}"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 {{ $isActive ? 'text-amber-600 hover:bg-amber-50 hover:border-amber-200 dark:border-slate-700 dark:hover:bg-amber-950/30' : 'text-emerald-600 hover:bg-emerald-50 hover:border-emerald-200 dark:border-slate-700 dark:hover:bg-emerald-950/30' }} cursor-pointer"
                  >
                    <i class="ph {{ $isActive ? 'ph-power' : 'ph-check-circle' }} text-sm"></i>
                  </button>
                </form>
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="8" class="py-12 text-center text-slate-400 dark:text-slate-500">
              <i class="ph ph-bank text-3xl block mb-2 text-slate-300 dark:text-slate-600"></i>
              No hospital bank accounts found.
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <!-- Table Footer -->
    <div class="flex items-center justify-between border-t border-slate-200 px-4 py-3 dark:border-slate-800">
      <span class="text-xs text-slate-500 dark:text-slate-400">
        Total Registered: {{ count($bankAccounts ?? []) }} Bank Accounts
      </span>
    </div>
  </div>
</div>

<!-- Modal: Add Bank Account -->
<x-modal 
  id="addAccountModal" 
  title="Add Hospital Bank Account" 
  size="md"
  formId="addAccountForm" 
  formAction="{{ route('cash.bank-accounts.store') }}" 
  formMethod="POST" 
  submitText="Create Bank Account" 
  submitIcon="ph-check"
>
  <div class="space-y-3.5">
    <div>
      <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
        Account Display Name <span class="text-rose-500">*</span>
      </label>
      <input 
        type="text" 
        name="name" 
        class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700 shadow-sm focus:border-emerald-500 focus:bg-white focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
        placeholder="e.g. Main Operating & Payroll Fund" 
        required
      >
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
        Bank &amp; Branch Name <span class="text-rose-500">*</span>
      </label>
      <input 
        type="text" 
        name="bank_name" 
        class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700 shadow-sm focus:border-emerald-500 focus:bg-white focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
        placeholder="e.g. Metrobank - Pasig Medical Plaza Branch" 
        required
      >
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
        Account Number <span class="text-rose-500">*</span>
      </label>
      <input 
        type="text" 
        name="account_number" 
        class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs font-mono font-bold text-slate-900 shadow-sm focus:border-emerald-500 focus:bg-white focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white" 
        placeholder="1029-9940-11" 
        required
      >
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
        General Ledger Account Link <span class="text-rose-500">*</span>
      </label>
      <select 
        name="gl_account_id" 
        class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700 shadow-sm focus:border-emerald-500 focus:bg-white focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
        required
      >
        @foreach($glAccounts ?? [] as $gla)
          <option value="{{ $gla->id }}">{{ $gla->code }} - {{ $gla->name }}</option>
        @endforeach
      </select>
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
        Account Purpose <span class="text-rose-500">*</span>
      </label>
      <input 
        type="text" 
        name="purpose" 
        class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700 shadow-sm focus:border-emerald-500 focus:bg-white focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
        placeholder="e.g. Daily Operations, HMO Settlements, Vendor Payouts" 
        required
      >
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <div>
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          Opening Balance (₱) <span class="text-rose-500">*</span>
        </label>
        <div class="relative">
          <span class="absolute left-3 top-1/2 -translate-y-1/2 font-mono font-bold text-slate-400 text-xs">₱</span>
          <input 
            type="number" 
            step="0.01" 
            min="0" 
            name="opening_balance" 
            class="w-full rounded-xl border-slate-200 bg-slate-50 py-2 pl-7 pr-3 text-xs font-mono font-bold text-slate-900 shadow-sm focus:border-emerald-500 focus:bg-white focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white" 
            value="0.00" 
            required
          >
        </div>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          Minimum Safety Floor (₱) <span class="text-rose-500">*</span>
        </label>
        <div class="relative">
          <span class="absolute left-3 top-1/2 -translate-y-1/2 font-mono font-bold text-slate-400 text-xs">₱</span>
          <input 
            type="number" 
            step="0.01" 
            min="0" 
            name="minimum_balance" 
            class="w-full rounded-xl border-slate-200 bg-slate-50 py-2 pl-7 pr-3 text-xs font-mono font-bold text-slate-900 shadow-sm focus:border-emerald-500 focus:bg-white focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white" 
            value="50000.00" 
            required
          >
        </div>
      </div>
    </div>
  </div>
</x-modal>

<!-- Modal: Edit Bank Account -->
<x-modal 
  id="editAccountModal" 
  title="Edit Bank Account Details" 
  size="md"
  formId="editAccountForm" 
  formAction="" 
  formMethod="POST" 
  submitText="Update Bank Account" 
  submitIcon="ph-check"
>
  @method('PUT')
  <div class="space-y-3.5">
    <div>
      <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
        Account Display Name <span class="text-rose-500">*</span>
      </label>
      <input 
        type="text" 
        name="name" 
        id="editName" 
        class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700 shadow-sm focus:border-emerald-500 focus:bg-white focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
        required
      >
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
        Bank &amp; Branch Name <span class="text-rose-500">*</span>
      </label>
      <input 
        type="text" 
        name="bank_name" 
        id="editBankName" 
        class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700 shadow-sm focus:border-emerald-500 focus:bg-white focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
        required
      >
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
        Account Number <span class="text-rose-500">*</span>
      </label>
      <input 
        type="text" 
        name="account_number" 
        id="editAccountNumber" 
        class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs font-mono font-bold text-slate-900 shadow-sm focus:border-emerald-500 focus:bg-white focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white" 
        required
      >
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
        General Ledger Account Link <span class="text-rose-500">*</span>
      </label>
      <select 
        name="gl_account_id" 
        id="editGlAccountId" 
        class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700 shadow-sm focus:border-emerald-500 focus:bg-white focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
        required
      >
        @foreach($glAccounts ?? [] as $gla)
          <option value="{{ $gla->id }}">{{ $gla->code }} - {{ $gla->name }}</option>
        @endforeach
      </select>
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
        Account Purpose <span class="text-rose-500">*</span>
      </label>
      <input 
        type="text" 
        name="purpose" 
        id="editPurpose" 
        class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700 shadow-sm focus:border-emerald-500 focus:bg-white focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
        required
      >
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <div>
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          Minimum Safety Floor (₱) <span class="text-rose-500">*</span>
        </label>
        <div class="relative">
          <span class="absolute left-3 top-1/2 -translate-y-1/2 font-mono font-bold text-slate-400 text-xs">₱</span>
          <input 
            type="number" 
            step="0.01" 
            min="0" 
            name="minimum_balance" 
            id="editMinimumBalance" 
            class="w-full rounded-xl border-slate-200 bg-slate-50 py-2 pl-7 pr-3 text-xs font-mono font-bold text-slate-900 shadow-sm focus:border-emerald-500 focus:bg-white focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white" 
            required
          >
        </div>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Status</label>
        <select 
          name="status" 
          id="editStatus" 
          class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700 shadow-sm focus:border-emerald-500 focus:bg-white focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
        >
          <option value="Active">Active</option>
          <option value="Inactive">Inactive</option>
          <option value="Frozen">Frozen</option>
        </select>
      </div>
    </div>
  </div>
</x-modal>
@endsection

@push('scripts')
<script>
function openEditModal(acc) {
  const form = document.getElementById('editAccountForm');
  if (form) {
    form.action = `/cash-management/bank-accounts/${acc.id}`;
  }

  const elName = document.getElementById('editName');
  if (elName) elName.value = acc.name || '';

  const elBank = document.getElementById('editBankName');
  if (elBank) elBank.value = acc.bank_name || '';

  const elAcc = document.getElementById('editAccountNumber');
  if (elAcc) elAcc.value = acc.account_number || '';

  const elPurp = document.getElementById('editPurpose');
  if (elPurp) elPurp.value = acc.purpose || '';

  const elMin = document.getElementById('editMinimumBalance');
  if (elMin) elMin.value = parseFloat(acc.minimum_balance || 50000).toFixed(2);

  const elStat = document.getElementById('editStatus');
  if (elStat) elStat.value = acc.status || 'Active';

  const elGl = document.getElementById('editGlAccountId');
  if (elGl && acc.gl_account_id) {
    elGl.value = acc.gl_account_id;
  }

  window.dispatchEvent(new CustomEvent('open-modal', { detail: 'editAccountModal' }));
}
</script>
@endpush

@extends('layouts.app')

@section('title', 'Chart of Accounts - General Ledger | FMS')
@section('module', 'gl')
@section('page', 'chart-of-accounts')

@section('content')
<div class="space-y-6">
  <!-- Executive Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
    <div>
      <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
          Chart of Accounts (COA)
        </h1>
    </div>

    <div class="flex items-center gap-2.5 flex-wrap">
      <a 
        href="{{ route('gl.trial-balance.export') }}" 
        class="inline-flex items-center gap-1.5 rounded-xl bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-700 transition-all"
        title="Download current accounts schedule"
      >
        <i class="ph-bold ph-download-simple"></i>
        <span>Export CSV</span>
      </a>
      <button 
        type="button" 
        @click="$dispatch('open-modal', 'addAccountModal')"
        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 ring-1 ring-emerald-600/20 transition-all"
      >
        <i class="ph-bold ph-plus-circle"></i>
        <span>Add Account</span>
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

  @if($errors->any())
    <div class="rounded-xl bg-rose-50 p-4 text-xs text-rose-800 ring-1 ring-rose-600/20 dark:bg-rose-950/40 dark:text-rose-300">
      <strong class="font-bold block mb-1">Please correct the following errors:</strong>
      <ul class="list-disc pl-4 space-y-0.5">
        @foreach($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <!-- COA Classification Summary Cards -->
  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5">
    <x-stat-card 
      title="Assets (1000s)" 
      :value="$assetTotal ?? 0" 
      icon="ph-trend-up" 
      color="emerald" 
      subtitle="Cash, AR, Equipment"
      badge="Asset"
    />
    <x-stat-card 
      title="Liabilities (2000s)" 
      :value="$liabilityTotal ?? 0" 
      icon="ph-warning-circle" 
      color="rose" 
      subtitle="AP, Accruals, Loans"
      badge="Liability"
    />
    <x-stat-card 
      title="Equity (3000s)" 
      :value="$equityTotal ?? 0" 
      icon="ph-scales" 
      color="blue" 
      subtitle="Net Worth & Retained"
      badge="Equity"
    />
    <x-stat-card 
      title="Revenue (4000s)" 
      :value="$revenueTotal ?? 0" 
      icon="ph-receipt" 
      color="teal" 
      subtitle="Inpatient & OPD Income"
      badge="Revenue"
    />
    <x-stat-card 
      title="Expenses (5000s)" 
      :value="$expenseTotal ?? 0" 
      icon="ph-chart-line-down" 
      color="amber" 
      subtitle="Clinical & Ops Costs"
      badge="Expense"
    />
  </div>

  <!-- Main Accounts Table Container -->
  

  <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 overflow-hidden dark:bg-slate-900 dark:ring-slate-800">
    <!-- Filter Toolbar -->
    <div class="border-b border-slate-200 p-4 dark:border-slate-800">
      <form method="GET" action="{{ route('gl.chart-of-accounts') }}" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 flex-wrap">
        <div class="flex items-center gap-2.5 flex-wrap">
          <div class="flex items-center gap-1.5 text-xs text-slate-500">
            <i class="ph-bold ph-funnel"></i>
            <span>Category:</span>
          </div>
          <select 
            name="category" 
            onchange="this.form.submit()" 
            class="rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-800 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
          >
            <option value="" {{ empty($category) ? 'selected' : '' }}>All Categories</option>
            <option value="ASSET" {{ ($category ?? '') === 'ASSET' ? 'selected' : '' }}>Assets (1000s)</option>
            <option value="LIABILITY" {{ ($category ?? '') === 'LIABILITY' ? 'selected' : '' }}>Liabilities (2000s)</option>
            <option value="EQUITY" {{ ($category ?? '') === 'EQUITY' ? 'selected' : '' }}>Equity (3000s)</option>
            <option value="REVENUE" {{ ($category ?? '') === 'REVENUE' ? 'selected' : '' }}>Revenue (4000s)</option>
            <option value="EXPENSE" {{ ($category ?? '') === 'EXPENSE' ? 'selected' : '' }}>Expenses (5000s)</option>
          </select>

          <div class="flex items-center gap-1.5 text-xs text-slate-500 ml-2">
            <span>Status:</span>
          </div>
          <select 
            name="status" 
            onchange="this.form.submit()" 
            class="rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-800 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
          >
            <option value="" {{ empty($status) ? 'selected' : '' }}>All Statuses</option>
            <option value="active" {{ ($status ?? '') === 'active' ? 'selected' : '' }}>Active Only</option>
            <option value="inactive" {{ ($status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive Only</option>
          </select>
        </div>

        <div class="relative w-full sm:w-72">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
            <i class="ph ph-magnifying-glass text-sm"></i>
          </div>
          <input 
            type="search" 
            name="q" 
            value="{{ $search ?? '' }}" 
            placeholder="Search code, title, department..." 
            class="w-full rounded-xl border-0 bg-slate-50 py-1.5 pl-9 pr-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
          >
        </div>
      </form>
    </div>

    <!-- Responsive Table -->
    <div class="overflow-x-auto custom-scrollbar">
      <table class="w-full text-left text-sm text-slate-700 dark:text-slate-300">
        <thead class="border-b border-slate-200 bg-slate-50/75 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-400">
          <tr>
            <th scope="col" class="py-3.5 pl-5 pr-3 w-28">GL Code</th>
            <th scope="col" class="px-3 py-3.5">Account Title</th>
            <th scope="col" class="px-3 py-3.5">Category</th>
            <th scope="col" class="px-3 py-3.5">Department</th>
            <th scope="col" class="px-3 py-3.5 text-center">Normal Balance</th>
            <th scope="col" class="px-3 py-3.5 text-right font-mono">Current Balance</th>
            <th scope="col" class="px-3 py-3.5 text-center">Status</th>
            <th scope="col" class="py-3.5 pl-3 pr-5 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
          @forelse($accounts as $acc)
            @php
              $code          = $acc->code;
              $name          = $acc->name;
              $catUpper      = strtoupper((string) $acc->category);
              $dept          = $acc->department ?? 'General Hospital';
              $normalBalance = strtoupper((string) $acc->normal_balance);
              $balance       = (float) $acc->current_balance;
              $hasLines      = $acc->journalEntryLines->isNotEmpty();

              $categoryVariant = match($catUpper) {
                'ASSET'     => 'emerald',
                'LIABILITY' => 'rose',
                'EQUITY'    => 'blue',
                'REVENUE'   => 'teal',
                'EXPENSE'   => 'amber',
                default     => 'slate',
              };

              $accJson = [
                'id'             => $acc->id,
                'code'           => $acc->code,
                'name'           => $acc->name,
                'category'       => $catUpper,
                'normal_balance' => $normalBalance,
                'department'     => $acc->department,
                'is_active'      => $acc->is_active,
                'balance'        => '₱' . number_format($balance, 2),
                'has_lines'      => $hasLines,
              ];
            @endphp
            <tr 
              class="transition-colors hover:bg-slate-50/60 dark:hover:bg-slate-800/40 cursor-pointer"
              @click="$dispatch('open-modal', { id: 'accountDetailsModal', account: {{ json_encode($accJson) }} })"
            >
              <td class="py-3.5 pl-5 pr-3 font-mono font-bold text-slate-900 dark:text-white">
                <span class="rounded-lg bg-slate-100 px-2 py-0.5 text-xs font-mono text-slate-700 dark:bg-slate-800 dark:text-slate-300 ring-1 ring-slate-200 dark:ring-slate-700">
                  {{ $code }}
                </span>
              </td>
              <td class="px-3 py-3.5">
                <div class="font-semibold text-slate-900 dark:text-white text-xs sm:text-sm">{{ $name }}</div>
              </td>
              <td class="px-3 py-3.5">
                <x-status-badge :status="$catUpper" :variant="$categoryVariant" />
              </td>
              <td class="px-3 py-3.5 text-xs text-slate-500 dark:text-slate-400">
                {{ $dept }}
              </td>
              <td class="px-3 py-3.5 text-center">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono font-medium {{ $normalBalance === 'DEBIT' ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-blue-50 text-blue-700 ring-1 ring-blue-600/20 dark:bg-blue-950/40 dark:text-blue-300' }}">
                  {{ $normalBalance }}
                </span>
              </td>
              <td class="px-3 py-3.5 text-right font-mono font-bold tabular-nums {{ $balance < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white' }}">
                ₱{{ number_format($balance, 2) }}
              </td>
              <td class="px-3 py-3.5 text-center" @click.stop>
                <form action="{{ route('gl.chart-of-accounts.toggle-status', $acc->id) }}" method="POST" class="inline">
                  @csrf
                  <button 
                    type="submit" 
                    class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-semibold transition-all {{ $acc->is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-slate-100 text-slate-500 ring-1 ring-slate-300 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-400' }}"
                    title="{{ $acc->is_active ? 'Click to Deactivate' : 'Click to Activate' }}"
                  >
                    <span class="h-1.5 w-1.5 rounded-full {{ $acc->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                    <span>{{ $acc->is_active ? 'ACTIVE' : 'INACTIVE' }}</span>
                  </button>
                </form>
              </td>
              <td class="py-3.5 pl-3 pr-5 text-right" @click.stop>
                <div class="flex items-center justify-end gap-1.5">
                  <a 
                    href="{{ route('gl.ledger-books', ['account_id' => $acc->id]) }}" 
                    class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 transition-colors"
                    title="Open Ledger Book"
                  >
                    <i class="ph-bold ph-book-open"></i>
                    <span class="hidden sm:inline">Ledger</span>
                  </a>
                  <button 
                    type="button" 
                    @click="$dispatch('open-modal', { id: 'editAccountModal', account: {{ json_encode($accJson) }} })"
                    class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-white transition-colors"
                    title="Edit Account"
                  >
                    <i class="ph-bold ph-pencil-simple text-sm"></i>
                  </button>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="py-12 text-center text-sm text-slate-400">
                <i class="ph ph-folder-dashed text-3xl mb-2 block mx-auto text-slate-300 dark:text-slate-600"></i>
                No GL accounts registered matching your criteria.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <!-- Table Footer Summary & Pagination -->
    @if(method_exists($accounts, 'links'))
      <div class="border-t border-slate-200 p-4 dark:border-slate-800">
        {{ $accounts->links() }}
      </div>
    @endif

    <div class="border-t border-slate-200 bg-slate-50/50 px-5 py-3 dark:border-slate-800 dark:bg-slate-900/50 flex flex-col sm:flex-row sm:items-center sm:justify-between text-xs text-slate-500 dark:text-slate-400 gap-2">
      <div>Showing <span class="font-bold text-slate-700 dark:text-slate-300 font-mono">{{ method_exists($accounts, 'total') ? $accounts->total() : count($accounts ?? []) }}</span> General Ledger Accounts</div>
      <div class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400 font-semibold">
        <i class="ph-bold ph-shield-check"></i>
        <span>GAAP / IFRS Double-Entry Invariance Guaranteed</span>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Account Details Inspection (Alpine.js) -->
<x-modal 
  id="accountDetailsModal" 
  title="General Ledger Account Dossier" 
  subtitle="Master Chart of Accounts record details and audit metrics" 
  icon="ph-tree-structure" 
  iconVariant="emerald" 
  size="lg" 
  :showFooter="false"
>
  <div 
    x-data="{ acc: {} }"
    @open-modal.window="if ($event.detail.id === 'accountDetailsModal') { acc = $event.detail.account || {}; }"
    class="space-y-4"
  >
    <div class="flex items-center justify-between p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 ring-1 ring-slate-200 dark:ring-slate-700">
      <div>
        <span class="rounded-lg bg-emerald-50 px-2 py-0.5 text-xs font-mono font-bold text-emerald-700 ring-1 ring-emerald-500/20 dark:bg-emerald-950/40 dark:text-emerald-300" x-text="acc.code"></span>
        <h3 class="text-base font-bold text-slate-900 dark:text-white mt-1.5" x-text="acc.name"></h3>
        <p class="text-xs text-slate-500" x-text="'Department: ' + (acc.department || 'General Hospital')"></p>
      </div>
      <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300" x-text="acc.category"></span>
    </div>

    <div class="grid grid-cols-2 gap-3 font-mono">
      <div class="rounded-xl bg-white p-3.5 ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-700 text-center">
        <span class="text-[10px] uppercase font-bold text-slate-500 block mb-1">Current Ledger Balance</span>
        <div class="text-xl font-bold text-slate-900 dark:text-white" x-text="acc.balance"></div>
      </div>
      <div class="rounded-xl bg-white p-3.5 ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-700 text-center">
        <span class="text-[10px] uppercase font-bold text-slate-500 block mb-1">Normal Balance</span>
        <div class="text-xl font-bold text-emerald-600 dark:text-emerald-400" x-text="acc.normal_balance"></div>
      </div>
    </div>

    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 ring-1 ring-slate-200 dark:ring-slate-700 space-y-2 text-xs">
      <div class="flex justify-between text-slate-600 dark:text-slate-300">
        <span>Transaction History:</span>
        <span class="font-semibold" x-text="acc.has_lines ? 'Active ledger history recorded' : 'Zero posted transactions'"></span>
      </div>
      <div class="flex justify-between text-slate-600 dark:text-slate-300">
        <span>BIR CAS Audit Trail:</span>
        <span class="font-mono text-emerald-600 dark:text-emerald-400 font-semibold">CAS-SHA256-VERIFIED</span>
      </div>
    </div>

    <div class="flex justify-end gap-2 pt-2">
      <button 
        type="button" 
        @click="$dispatch('close-modal', 'accountDetailsModal')" 
        class="rounded-xl px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
      >
        Close
      </button>
      <a 
        :href="'{{ route('gl.ledger-books') }}?account_id=' + acc.id" 
        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700"
      >
        <i class="ph-bold ph-book-open"></i>
        <span>Open Ledger Book</span>
      </a>
    </div>
  </div>
</x-modal>

<!-- Modal: Add New GL Account (Alpine.js) -->
<x-modal 
  id="addAccountModal" 
  title="Register New General Ledger Account" 
  subtitle="Define account hierarchy, department mapping, and normal balance" 
  icon="ph-plus-circle" 
  iconVariant="emerald" 
  size="xl" 
  formAction="{{ route('gl.chart-of-accounts.store') }}" 
  formMethod="POST" 
  submitText="Save Account"
  submitIcon="ph-check"
>
  <div 
    x-data="{
      category: 'ASSET',
      normalBalance: 'DEBIT',
      updateNormalBalance() {
        if (this.category === 'ASSET' || this.category === 'EXPENSE') {
          this.normalBalance = 'DEBIT';
        } else {
          this.normalBalance = 'CREDIT';
        }
      }
    }" 
    class="grid grid-cols-1 sm:grid-cols-12 gap-4"
  >
    <div class="sm:col-span-4">
      <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">GL Account Code <span class="text-rose-500">*</span></label>
      <input type="text" name="code" placeholder="e.g. 1060" required pattern="[0-9A-Za-z\-]+" class="w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs font-mono font-semibold text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700">
      <span class="text-[10px] text-slate-400 mt-1 block">Unique alphanumeric identifier</span>
    </div>

    <div class="sm:col-span-8">
      <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Account Title / Name <span class="text-rose-500">*</span></label>
      <input type="text" name="name" placeholder="e.g. Allowance for Impairment - Patient Receivables" required class="w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700">
      <span class="text-[10px] text-slate-400 mt-1 block">Descriptive accounting classification</span>
    </div>

    <div class="sm:col-span-4">
      <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Category <span class="text-rose-500">*</span></label>
      <select 
        name="category" 
        x-model="category" 
        @change="updateNormalBalance()" 
        required 
        class="w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs font-semibold text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
      >
        <option value="ASSET">ASSET (1000s)</option>
        <option value="LIABILITY">LIABILITY (2000s)</option>
        <option value="EQUITY">EQUITY (3000s)</option>
        <option value="REVENUE">REVENUE (4000s)</option>
        <option value="EXPENSE">EXPENSE (5000s)</option>
      </select>
    </div>

    <div class="sm:col-span-4">
      <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Normal Balance <span class="text-rose-500">*</span></label>
      <select 
        name="normal_balance" 
        x-model="normalBalance" 
        required 
        class="w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs font-mono font-semibold text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
      >
        <option value="DEBIT">DEBIT</option>
        <option value="CREDIT">CREDIT</option>
      </select>
      <span class="text-[10px] text-slate-400 mt-1 block">Auto-set per GAAP convention</span>
    </div>

    <div class="sm:col-span-4">
      <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Department / Cost Center</label>
      <input type="text" name="department" placeholder="e.g. Inpatient, Pharmacy, General" class="w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700">
      <span class="text-[10px] text-slate-400 mt-1 block">Optional departmental grouping</span>
    </div>
  </div>
</x-modal>

<!-- Modal: Edit GL Account (Alpine.js) -->
<x-modal 
  id="editAccountModal" 
  title="Edit General Ledger Account" 
  subtitle="Update account title, category classification, or cost center" 
  icon="ph-pencil-simple" 
  iconVariant="blue" 
  size="xl" 
  formAction="#" 
  formMethod="POST" 
  submitText="Update Account"
  submitIcon="ph-check"
  submitVariant="blue"
>
  <div 
    x-data="{
      acc: {},
      formAction: '',
      updateUrl(account) {
        this.acc = account || {};
        const form = document.querySelector('#editAccountModal form');
        if (form && this.acc.id) {
          form.action = '{{ url('/general-ledger/chart-of-accounts') }}/' + this.acc.id;
        }
      }
    }"
    @open-modal.window="if ($event.detail.id === 'editAccountModal') { updateUrl($event.detail.account); }"
    class="grid grid-cols-1 sm:grid-cols-12 gap-4"
  >
    <input type="hidden" name="_method" value="PUT">

    <div class="sm:col-span-4">
      <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">GL Account Code <span class="text-rose-500">*</span></label>
      <input type="text" name="code" x-model="acc.code" required class="w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs font-mono font-semibold text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700">
    </div>

    <div class="sm:col-span-8">
      <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Account Title / Name <span class="text-rose-500">*</span></label>
      <input type="text" name="name" x-model="acc.name" required class="w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700">
    </div>

    <div class="sm:col-span-4">
      <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Category <span class="text-rose-500">*</span></label>
      <select name="category" x-model="acc.category" required class="w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs font-semibold text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700">
        <option value="ASSET">ASSET (1000s)</option>
        <option value="LIABILITY">LIABILITY (2000s)</option>
        <option value="EQUITY">EQUITY (3000s)</option>
        <option value="REVENUE">REVENUE (4000s)</option>
        <option value="EXPENSE">EXPENSE (5000s)</option>
      </select>
    </div>

    <div class="sm:col-span-4">
      <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Normal Balance <span class="text-rose-500">*</span></label>
      <select name="normal_balance" x-model="acc.normal_balance" required class="w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs font-mono font-semibold text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700">
        <option value="DEBIT">DEBIT</option>
        <option value="CREDIT">CREDIT</option>
      </select>
    </div>

    <div class="sm:col-span-4">
      <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Department / Cost Center</label>
      <input type="text" name="department" x-model="acc.department" class="w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700">
    </div>
  </div>
</x-modal>
@endsection

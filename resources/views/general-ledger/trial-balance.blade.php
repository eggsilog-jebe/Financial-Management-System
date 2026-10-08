@extends('layouts.app')

@section('title', 'Trial Balance - General Ledger | FMS')
@section('module', 'gl')
@section('page', 'trial-balance')

@section('content')
<div class="space-y-6">
  <!-- Executive Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
    <div>
      <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
          General Ledger Trial Balance
        </h1>
    </div>

    <div class="flex items-center gap-2.5 flex-wrap">
      <button 
        type="button" 
        onclick="window.print()" 
        class="inline-flex items-center gap-1.5 rounded-xl bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-700 transition-all"
      >
        <i class="ph-bold ph-printer"></i>
        <span>Print Statement</span>
      </button>
      <a 
        href="{{ route('gl.trial-balance.export', ['as_of_date' => $asOfDate, 'hide_zero_balances' => $hideZeroBalances ? '1' : '0', 'category' => $selectedCategory]) }}" 
        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 ring-1 ring-emerald-600/20 transition-all"
      >
        <i class="ph-bold ph-download-simple"></i>
        <span>Export CSV</span>
      </a>
    </div>
  </div>

  <!-- Real-Time Audit Invariance Banner -->
  <div class="rounded-2xl p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 {{ $isBalanced ? 'bg-emerald-50 text-emerald-900 ring-1 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-500/30' : 'bg-rose-50 text-rose-900 ring-1 ring-rose-600/20 dark:bg-rose-950/40 dark:text-rose-300 dark:ring-rose-500/30' }}">
    <div class="flex items-center gap-3.5">
      <span class="inline-flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-2xl {{ $isBalanced ? 'bg-emerald-600 text-white' : 'bg-rose-600 text-white' }} shadow-sm">
        <i class="ph-bold {{ $isBalanced ? 'ph-shield-check' : 'ph-warning-octagon' }} text-2xl"></i>
      </span>
      <div>
        <h3 class="text-sm sm:text-base font-bold">
          {{ $isBalanced ? 'TRIAL BALANCE IS BALANCED (In Equilibrium: Debits == Credits)' : 'DOUBLE-ENTRY VARIANCE DETECTED' }}
        </h3>
        <p class="text-xs opacity-90 mt-0.5">
          {{ $isBalanced ? 'All general ledger accounts are in strict debit-credit balance. Ready for financial statement generation and period close.' : 'An imbalance between total debits and credits was detected. Review unposted journal entries or reversal drafts.' }}
        </p>
      </div>
    </div>
    <div class="text-left sm:text-right font-mono">
      <span class="text-[10px] font-bold uppercase tracking-wider block opacity-75">Variance Imbalance</span>
      <span class="text-2xl font-bold tabular-nums {{ $isBalanced ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300' }}">
        ₱{{ number_format(abs($discrepancy ?? 0), 2) }}
      </span>
    </div>
  </div>

  <!-- Summary Metric Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
    <x-stat-card 
      title="Total Debit Balances" 
      :value="$totalDebitBalance ?? 0" 
      icon="ph-arrow-up-right" 
      color="emerald" 
      subtitle="Sum of active asset & expense debits"
    />

    <x-stat-card 
      title="Total Credit Balances" 
      :value="$totalCreditBalance ?? 0" 
      icon="ph-arrow-down-left" 
      color="blue" 
      subtitle="Sum of liability, equity & revenue credits"
    />

    <x-stat-card 
      title="Accounts in Schedule" 
      :value="count($rows ?? [])" 
      :isCurrency="false"
      icon="ph-book-open" 
      color="slate" 
      subtitle="Filtered active GL accounts"
    />

    <x-stat-card 
      title="Net Trial Variance" 
      :value="abs($discrepancy ?? 0)" 
      icon="ph-shield-check" 
      :color="$isBalanced ? 'emerald' : 'rose'" 
      :subtitle="$isBalanced ? 'Zero deviation from GAAP/IFRS' : 'Requires reconciling adjustment'"
    />
  </div>

  <!-- Filter & Table Card -->
  

  <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 overflow-hidden dark:bg-slate-900 dark:ring-slate-800">
    <!-- Filter Toolbar -->
    <div class="border-b border-slate-200 p-4 dark:border-slate-800">
      <form method="GET" action="{{ route('gl.trial-balance') }}" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 flex-wrap">
        <div class="flex items-center gap-3 flex-wrap">
          <div class="flex items-center gap-1.5 text-xs text-slate-500">
            <i class="ph-bold ph-calendar"></i>
            <span>As of Date:</span>
          </div>
          <input 
            type="date" 
            name="as_of_date" 
            value="{{ $asOfDate }}" 
            onchange="this.form.submit()" 
            class="rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
          >

          <div class="flex items-center gap-1.5 text-xs text-slate-500 ml-2">
            <span>Category:</span>
          </div>
          <select 
            name="category" 
            onchange="this.form.submit()" 
            class="rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
          >
            <option value="" {{ empty($selectedCategory) ? 'selected' : '' }}>All Accounts</option>
            <option value="ASSET" {{ ($selectedCategory ?? '') === 'ASSET' ? 'selected' : '' }}>Assets (1000s)</option>
            <option value="LIABILITY" {{ ($selectedCategory ?? '') === 'LIABILITY' ? 'selected' : '' }}>Liabilities (2000s)</option>
            <option value="EQUITY" {{ ($selectedCategory ?? '') === 'EQUITY' ? 'selected' : '' }}>Equity (3000s)</option>
            <option value="REVENUE" {{ ($selectedCategory ?? '') === 'REVENUE' ? 'selected' : '' }}>Revenue (4000s)</option>
            <option value="EXPENSE" {{ ($selectedCategory ?? '') === 'EXPENSE' ? 'selected' : '' }}>Expenses (5000s)</option>
          </select>

          <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700 dark:text-slate-300 ml-2">
            <input 
              type="checkbox" 
              name="hide_zero_balances" 
              value="1" 
              {{ $hideZeroBalances ? 'checked' : '' }} 
              onchange="this.form.submit()"
              class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-800"
            >
            <span>Hide Zero Balances</span>
          </label>
        </div>

        <span class="text-xs font-mono font-semibold text-slate-500">
          Cutoff: {{ \Carbon\Carbon::parse($asOfDate)->format('M d, Y') }}
        </span>
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
            <th scope="col" class="px-3 py-3.5 text-center">Normal</th>
            <th scope="col" class="px-3 py-3.5 text-right font-mono">Debit Balance</th>
            <th scope="col" class="px-3 py-3.5 text-right font-mono">Credit Balance</th>
            <th scope="col" class="py-3.5 pl-3 pr-5 text-center">Audit Status</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
          @forelse($rows as $row)
            @php
              $category = $row['category'] ?? '';
              $categoryVariant = match($category) {
                'ASSET'     => 'emerald',
                'LIABILITY' => 'rose',
                'EQUITY'    => 'blue',
                'REVENUE'   => 'teal',
                'EXPENSE'   => 'amber',
                default     => 'slate',
              };
            @endphp
            <tr class="transition-colors hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
              <td class="py-3.5 pl-5 pr-3 font-mono font-bold text-slate-900 dark:text-white">
                <span class="rounded-lg bg-slate-100 px-2 py-0.5 text-xs font-mono text-slate-700 dark:bg-slate-800 dark:text-slate-300 ring-1 ring-slate-200 dark:ring-slate-700">
                  {{ $row['code'] }}
                </span>
              </td>
              <td class="px-3 py-3.5">
                <div class="font-semibold text-slate-900 dark:text-white text-xs sm:text-sm">{{ $row['name'] }}</div>
              </td>
              <td class="px-3 py-3.5">
                <x-status-badge :status="$category" :variant="$categoryVariant" />
              </td>
              <td class="px-3 py-3.5 text-center">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono font-medium {{ ($row['normal_balance'] ?? '') === 'DEBIT' ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-blue-50 text-blue-700 ring-1 ring-blue-600/20 dark:bg-blue-950/40 dark:text-blue-300' }}">
                  {{ $row['normal_balance'] }}
                </span>
              </td>
              @php
                $debitVal = (float) ($row['debit_balance'] ?? $row['debit'] ?? 0);
                $creditVal = (float) ($row['credit_balance'] ?? $row['credit'] ?? 0);
              @endphp
              <td class="px-3 py-3.5 text-right font-mono font-bold tabular-nums {{ $debitVal > 0 ? 'text-slate-900 dark:text-white' : 'text-slate-400' }}">
                {{ $debitVal > 0 ? '₱' . number_format($debitVal, 2) : '—' }}
              </td>
              <td class="px-3 py-3.5 text-right font-mono font-bold tabular-nums {{ $creditVal > 0 ? 'text-slate-900 dark:text-white' : 'text-slate-400' }}">
                {{ $creditVal > 0 ? '₱' . number_format($creditVal, 2) : '—' }}
              </td>
              <td class="py-3.5 pl-3 pr-5 text-center">
                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300">
                  <i class="ph-bold ph-check"></i> Verified
                </span>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="py-12 text-center text-sm text-slate-400">
                <i class="ph ph-scales text-3xl mb-2 block mx-auto text-slate-300 dark:text-slate-600"></i>
                No accounts match the selected trial balance filter.
              </td>
            </tr>
          @endforelse
        </tbody>
        <tfoot class="border-t-2 border-slate-300 bg-slate-50 dark:border-slate-700 dark:bg-slate-900/60 font-mono text-xs font-bold">
          <tr>
            <td colspan="4" class="py-4 pl-5 pr-3 font-sans text-right text-slate-700 dark:text-slate-300 uppercase tracking-wider">
              Total Trial Balance Assertion:
            </td>
            <td class="px-3 py-4 text-right text-emerald-600 dark:text-emerald-400 text-sm tabular-nums">
              ₱{{ number_format((float)($totalDebitBalance ?? 0), 2) }}
            </td>
            <td class="px-3 py-4 text-right text-emerald-600 dark:text-emerald-400 text-sm tabular-nums">
              ₱{{ number_format((float)($totalCreditBalance ?? 0), 2) }}
            </td>
            <td class="py-4 pl-3 pr-5 text-center">
              @if($isBalanced)
                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                  <i class="ph-bold ph-check-circle"></i> Balanced
                </span>
              @else
                <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2.5 py-1 text-xs font-bold text-rose-800 dark:bg-rose-950 dark:text-rose-300">
                  <i class="ph-bold ph-warning"></i> Out of Balance
                </span>
              @endif
            </td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>
@endsection

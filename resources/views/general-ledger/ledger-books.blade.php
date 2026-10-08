@extends('layouts.app')

@section('title', 'Ledger Books - General Ledger | FMS')
@section('module', 'gl')
@section('page', 'ledger-books')

@section('content')
<div class="space-y-6">
  <!-- Executive Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
    <div>
      <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
          General Ledger Account Books &amp; T-Accounts
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
        href="{{ route('gl.ledger-books.export', ['account_id' => $selectedAccountId, 'start_date' => $startDate, 'end_date' => $endDate, 'fiscal_year' => $fiscalYear]) }}" 
        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 ring-1 ring-emerald-600/20 transition-all"
      >
        <i class="ph-bold ph-download-simple"></i>
        <span>Export CSV</span>
      </a>
    </div>
  </div>

  <!-- Account Selector & Filter Form -->
  <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
    <form method="GET" action="{{ route('gl.ledger-books') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3.5 items-end">
      <!-- Account Selector -->
      <div class="sm:col-span-4">
        <label for="accountSelect" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">
          <i class="ph-bold ph-book me-1 text-emerald-600"></i> Select GL Account:
        </label>
        <select 
          name="account_id" 
          id="accountSelect" 
          class="w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs font-semibold text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
          onchange="this.form.submit()"
        >
          @foreach($accounts as $acc)
            <option value="{{ $acc->id }}" {{ $acc->id === (int)$selectedAccountId ? 'selected' : '' }}>
              {{ $acc->code }} — {{ $acc->name }} ({{ $acc->category }})
            </option>
          @endforeach
        </select>
      </div>

      <!-- Start Date -->
      <div class="sm:col-span-2">
        <label for="startDateInput" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Start Date:</label>
        <input 
          type="date" 
          name="start_date" 
          id="startDateInput" 
          class="w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700" 
          value="{{ $startDate ?? '' }}" 
          onchange="this.form.submit()"
        >
      </div>

      <!-- End Date -->
      <div class="sm:col-span-2">
        <label for="endDateInput" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">End Date:</label>
        <input 
          type="date" 
          name="end_date" 
          id="endDateInput" 
          class="w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700" 
          value="{{ $endDate ?? '' }}" 
          onchange="this.form.submit()"
        >
      </div>

      <!-- Fiscal Year -->
      <div class="sm:col-span-2">
        <label for="fiscalYearSelect" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Fiscal Year:</label>
        <select 
          name="fiscal_year" 
          id="fiscalYearSelect" 
          class="w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs font-semibold text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700" 
          onchange="this.form.submit()"
        >
          <option value="" {{ empty($fiscalYear) ? 'selected' : '' }}>All Years</option>
          @for($y = (int)date('Y'); $y >= (int)date('Y') - 3; $y--)
            <option value="{{ $y }}" {{ ($fiscalYear ?? '') == (string)$y ? 'selected' : '' }}>FY {{ $y }}</option>
          @endfor
        </select>
      </div>

      <!-- Reset -->
      <div class="sm:col-span-2">
        <a 
          href="{{ route('gl.ledger-books', ['account_id' => $selectedAccountId]) }}" 
          class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 transition-colors"
        >
          <i class="ph-bold ph-arrow-counter-clockwise"></i>
          <span>Reset Filter</span>
        </a>
      </div>
    </form>
  </div>

  @if($statement && $statement['account'])
  @php
    $acc = $statement['account'];
    $isDebitNormal = strtoupper((string) $acc->normal_balance) === 'DEBIT';
  @endphp

  <!-- Account Header Summary Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
      <div class="flex items-center justify-between">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Account Classification</span>
        <span class="font-mono text-xs font-bold rounded-lg bg-slate-100 px-2 py-0.5 dark:bg-slate-800 dark:text-slate-200">{{ $acc->code }}</span>
      </div>
      <h3 class="mt-2 text-base font-bold text-slate-900 dark:text-white truncate">{{ $acc->name }}</h3>
      <div class="mt-2 flex items-center gap-1.5">
        <x-status-badge :status="$acc->category" />
        <span class="text-[11px] font-mono font-semibold text-slate-500">{{ $acc->normal_balance }} NORMAL</span>
      </div>
    </div>

    <x-stat-card 
      title="Opening Balance" 
      :value="$statement['beginning_balance'] ?? $statement['opening_balance'] ?? 0" 
      icon="ph-clock-countdown" 
      color="slate" 
      subtitle="Prior period cutoff"
    />

    <x-stat-card 
      title="Period Debit Flow" 
      :value="$statement['period_debits'] ?? $statement['total_debit'] ?? 0" 
      icon="ph-arrow-up-right" 
      color="emerald" 
      subtitle="Total validated debits"
    />

    <x-stat-card 
      title="Current Running Balance" 
      :value="$statement['ending_balance'] ?? $statement['closing_balance'] ?? 0" 
      icon="ph-scales" 
      color="blue" 
      subtitle="Net ending ledger position"
    />
  </div>

  <!-- Historical Running Ledger Table -->
  

  <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 overflow-hidden dark:bg-slate-900 dark:ring-slate-800">
    <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800 flex items-center justify-between">
      <div>
        <h3 class="text-base font-bold text-slate-900 dark:text-white">Transaction Line History</h3>
        <p class="text-xs text-slate-500 dark:text-slate-400">Sequential chronological ledger postings</p>
      </div>
      <span class="text-xs font-mono font-semibold text-slate-500">{{ count($statement['rows'] ?? $statement['lines'] ?? []) }} Entries</span>
    </div>

    <div class="overflow-x-auto custom-scrollbar">
      <table class="w-full text-left text-sm text-slate-700 dark:text-slate-300">
        <thead class="border-b border-slate-200 bg-slate-50/75 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-400">
          <tr>
            <th scope="col" class="py-3.5 pl-5 pr-3">Date</th>
            <th scope="col" class="px-3 py-3.5">Ref #</th>
            <th scope="col" class="px-3 py-3.5">Narrative / Line Memo</th>
            <th scope="col" class="px-3 py-3.5 text-right font-mono">Debit (₱)</th>
            <th scope="col" class="px-3 py-3.5 text-right font-mono">Credit (₱)</th>
            <th scope="col" class="px-3 py-3.5 text-right font-mono">Running Balance (₱)</th>
            <th scope="col" class="py-3.5 pl-3 pr-5 text-center">Status</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-mono text-xs">
          <!-- Opening Balance Row -->
          <tr class="bg-slate-50/50 dark:bg-slate-800/30">
            <td class="py-3 pl-5 pr-3 text-slate-500">—</td>
            <td class="px-3 py-3 text-slate-500">OPENING</td>
            <td class="px-3 py-3 font-sans font-medium text-slate-600 dark:text-slate-400">Beginning Balance as of period start</td>
            <td class="px-3 py-3 text-right text-slate-400">—</td>
            <td class="px-3 py-3 text-right text-slate-400">—</td>
            <td class="px-3 py-3 text-right font-bold text-slate-900 dark:text-white tabular-nums">
              ₱{{ number_format((float)($statement['beginning_balance'] ?? $statement['opening_balance'] ?? 0), 2) }}
            </td>
            <td class="py-3 pl-3 pr-5 text-center">
              <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                SETTLED
              </span>
            </td>
          </tr>

          @forelse($statement['rows'] ?? $statement['lines'] ?? [] as $line)
            <tr class="transition-colors hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
              <td class="py-3 pl-5 pr-3 text-slate-600 dark:text-slate-400">
                {{ \Carbon\Carbon::parse($line['entry_date'])->format('Y-m-d') }}
              </td>
              <td class="px-3 py-3 font-bold text-slate-900 dark:text-white">
                <a href="{{ route('gl.journal-entries', ['q' => $line['reference_number']]) }}" class="hover:text-emerald-600 hover:underline">
                  {{ $line['reference_number'] }}
                </a>
              </td>
              <td class="px-3 py-3 font-sans text-slate-800 dark:text-slate-200">
                <div class="font-medium text-xs">{{ $line['memo'] ?: $line['description'] }}</div>
                @if($line['memo'] && $line['memo'] !== $line['description'])
                  <div class="text-[11px] text-slate-400">{{ $line['description'] }}</div>
                @endif
              </td>
              <td class="px-3 py-3 text-right font-bold tabular-nums {{ (float)$line['debit'] > 0 ? 'text-slate-900 dark:text-white' : 'text-slate-400' }}">
                {{ (float)$line['debit'] > 0 ? '₱' . number_format((float)$line['debit'], 2) : '—' }}
              </td>
              <td class="px-3 py-3 text-right font-bold tabular-nums {{ (float)$line['credit'] > 0 ? 'text-slate-900 dark:text-white' : 'text-slate-400' }}">
                {{ (float)$line['credit'] > 0 ? '₱' . number_format((float)$line['credit'], 2) : '—' }}
              </td>
              <td class="px-3 py-3 text-right font-bold tabular-nums text-slate-900 dark:text-white">
                ₱{{ number_format((float)$line['running_balance'], 2) }}
              </td>
              <td class="py-3 pl-3 pr-5 text-center font-sans">
                <x-status-badge :status="$line['status'] ?? 'POSTED'" />
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="py-10 text-center text-sm font-sans text-slate-400">
                <i class="ph ph-receipt-x text-3xl mb-2 block mx-auto text-slate-300 dark:text-slate-600"></i>
                No transaction line items recorded for this GL account in the selected period.
              </td>
            </tr>
          @endforelse
        </tbody>
        <tfoot class="border-t border-slate-200 bg-slate-50/50 dark:border-slate-800 dark:bg-slate-900/50 font-mono text-xs font-bold">
          <tr>
            <td colspan="3" class="py-3.5 pl-5 pr-3 font-sans text-right text-slate-500 uppercase">Period Totals &amp; Closing:</td>
            <td class="px-3 py-3.5 text-right text-emerald-600 dark:text-emerald-400 tabular-nums">
              ₱{{ number_format((float)($statement['period_debits'] ?? $statement['total_debit'] ?? 0), 2) }}
            </td>
            <td class="px-3 py-3.5 text-right text-emerald-600 dark:text-emerald-400 tabular-nums">
              ₱{{ number_format((float)($statement['period_credits'] ?? $statement['total_credit'] ?? 0), 2) }}
            </td>
            <td class="px-3 py-3.5 text-right text-slate-900 dark:text-white tabular-nums">
              ₱{{ number_format((float)($statement['ending_balance'] ?? $statement['closing_balance'] ?? 0), 2) }}
            </td>
            <td class="py-3.5 pl-3 pr-5"></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
  @endif
</div>
@endsection

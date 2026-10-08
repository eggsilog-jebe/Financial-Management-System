@extends('layouts.app')

@section('title', 'Period-End Closing - General Ledger | FMS')
@section('module', 'gl')
@section('page', 'period-end-closing')

@section('content')
<div class="space-y-6">
  <!-- Executive Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
    <div>
      <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
          Period-End Financial Closing &amp; GL Locking
        </h1>
    </div>

    <div class="flex items-center gap-2.5 flex-wrap">
      <button 
        type="button" 
        @click="$dispatch('open-modal', 'initYearModal')"
        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 ring-1 ring-emerald-600/20 transition-all"
      >
        <i class="ph-bold ph-calendar-plus"></i>
        <span>Start New Fiscal Year</span>
      </button>
      <a 
        href="{{ route('gl.trial-balance') }}" 
        class="inline-flex items-center gap-1.5 rounded-xl bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-700 transition-all"
      >
        <i class="ph-bold ph-shield-check"></i>
        <span>Pre-Closing Trial Balance</span>
      </a>
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

  <!-- 3-Step Closing Workflow Guide -->
  <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="flex items-start gap-3 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 ring-1 ring-slate-200 dark:ring-slate-700">
        <span class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-500/20 dark:bg-emerald-950/40 dark:text-emerald-300 font-bold text-xs">
          1
        </span>
        <div>
          <h4 class="text-xs font-bold text-slate-900 dark:text-white">Verify Equilibrium</h4>
          <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Ensure all patient billing, purchase bills &amp; journals are balanced in Trial Balance.</p>
        </div>
      </div>

      <div class="flex items-start gap-3 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 ring-1 ring-slate-200 dark:ring-slate-700">
        <span class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 ring-1 ring-amber-500/20 dark:bg-amber-950/40 dark:text-amber-300 font-bold text-xs">
          2
        </span>
        <div>
          <h4 class="text-xs font-bold text-slate-900 dark:text-white">Soft Freeze (Lock Period)</h4>
          <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Blocks non-management staff from posting new transactions into this cutoff month.</p>
        </div>
      </div>

      <div class="flex items-start gap-3 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 ring-1 ring-slate-200 dark:ring-slate-700">
        <span class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-600 ring-1 ring-rose-500/20 dark:bg-rose-950/40 dark:text-rose-300 font-bold text-xs">
          3
        </span>
        <div>
          <h4 class="text-xs font-bold text-slate-900 dark:text-white">CFO Hard Close</h4>
          <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Permanently closes period, transfers surplus to Retained Earnings &amp; writes CAS hash.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Metric Summary Cards -->
  @php
    $openCount = $periods->where('status', 'OPEN')->count();
    $lockedCount = $periods->where('status', 'LOCKED')->count();
    $closedCount = $periods->where('status', 'CLOSED')->count();
  @endphp
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
    <x-stat-card 
      title="Open Fiscal Months" 
      :value="$openCount" 
      :isCurrency="false" 
      icon="ph-lock-open" 
      color="emerald" 
      subtitle="Accepting subledger postings"
      badge="Open"
    />
    <x-stat-card 
      title="Soft-Locked Periods" 
      :value="$lockedCount" 
      :isCurrency="false" 
      icon="ph-lock" 
      color="amber" 
      subtitle="Under managerial audit review"
      badge="Soft Lock"
    />
    <x-stat-card 
      title="Hard Closed Periods" 
      :value="$closedCount" 
      :isCurrency="false" 
      icon="ph-shield-check" 
      color="slate" 
      subtitle="Immutable finalized ledgers"
      badge="Closed"
    />
  </div>

  <!-- Fiscal Periods Table Card -->
  

  <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 overflow-hidden dark:bg-slate-900 dark:ring-slate-800">
    <div class="border-b border-slate-200 p-4 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h3 class="text-base font-bold text-slate-900 dark:text-white">Fiscal Period Registry</h3>
        <p class="text-xs text-slate-500 dark:text-slate-400">Monthly fiscal cutoff status and audit timestamps</p>
      </div>

      <!-- Fiscal Year Filter -->
      <form method="GET" action="{{ route('gl.period-end-closing') }}" class="flex items-center gap-2">
        <label for="yearSelect" class="text-xs font-semibold text-slate-500">Fiscal Year:</label>
        <select 
          name="fiscal_year" 
          id="yearSelect" 
          onchange="this.form.submit()" 
          class="rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
        >
          @foreach($availableYears ?? $allYears ?? [(string)date('Y')] as $year)
            <option value="{{ $year }}" {{ (int)$selectedYear === (int)$year ? 'selected' : '' }}>FY {{ $year }}</option>
          @endforeach
        </select>
      </form>
    </div>

    <div class="overflow-x-auto custom-scrollbar">
      <table class="w-full text-left text-sm text-slate-700 dark:text-slate-300">
        <thead class="border-b border-slate-200 bg-slate-50/75 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-400">
          <tr>
            <th scope="col" class="py-3.5 pl-5 pr-3">Period / Month</th>
            <th scope="col" class="px-3 py-3.5">Date Range</th>
            <th scope="col" class="px-3 py-3.5 text-center">Status</th>
            <th scope="col" class="px-3 py-3.5">Lock Information</th>
            <th scope="col" class="px-3 py-3.5">Closing Audit</th>
            <th scope="col" class="py-3.5 pl-3 pr-5 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
          @forelse($periods as $period)
            @php
              $status = strtoupper((string) $period->status);
              $statusVariant = match($status) {
                'OPEN'   => 'emerald',
                'LOCKED' => 'amber',
                'CLOSED' => 'slate',
                default  => 'slate',
              };
            @endphp
            <tr class="transition-colors hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
              <td class="py-3.5 pl-5 pr-3">
                <div class="font-bold text-slate-900 dark:text-white text-xs sm:text-sm">{{ $period->period_name }}</div>
                <div class="text-[11px] font-mono text-slate-500">Period #{{ $period->period_number }}</div>
              </td>
              <td class="px-3 py-3.5 text-xs font-mono text-slate-600 dark:text-slate-400">
                {{ $period->start_date->format('M d, Y') }} — {{ $period->end_date->format('M d, Y') }}
              </td>
              <td class="px-3 py-3.5 text-center">
                <x-status-badge :status="$status" :variant="$statusVariant" />
              </td>
              <td class="px-3 py-3.5 text-xs text-slate-500 dark:text-slate-400">
                @if($period->locked_at)
                  <div class="font-medium text-slate-900 dark:text-white">Locked: {{ $period->locked_at->format('Y-m-d H:i') }}</div>
                  <div class="text-[11px]">By: {{ $period->lockedBy?->name ?? 'System' }}</div>
                @else
                  <span class="text-slate-400">Not locked</span>
                @endif
              </td>
              <td class="px-3 py-3.5 text-xs text-slate-500 dark:text-slate-400">
                @if($period->closed_at)
                  <div class="font-medium text-slate-900 dark:text-white">Closed: {{ $period->closed_at->format('Y-m-d H:i') }}</div>
                  <div class="text-[11px]">CFO: {{ $period->closedBy?->name ?? 'System' }}</div>
                @else
                  <span class="text-slate-400">Not closed</span>
                @endif
              </td>
              <td class="py-3.5 pl-3 pr-5 text-right">
                <div class="flex items-center justify-end gap-2">
                  @if($status === 'OPEN')
                    <form action="{{ route('gl.period-end-closing.lock', $period->id) }}" method="POST" class="inline" onsubmit="return confirm('Soft-lock {{ $period->period_name }}? Staff will not be able to post entries to this period.');">
                      @csrf
                      <button 
                        type="submit" 
                        class="inline-flex items-center gap-1 rounded-xl bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 ring-1 ring-amber-600/20 hover:bg-amber-100 dark:bg-amber-950/40 dark:text-amber-300 transition-all"
                        title="Block non-manager postings"
                      >
                        <i class="ph-bold ph-lock"></i>
                        <span>Lock (Soft)</span>
                      </button>
                    </form>
                  @elseif($status === 'LOCKED')
                    <form action="{{ route('gl.period-end-closing.close', $period->id) }}" method="POST" class="inline" onsubmit="return confirm('FINAL CFO HARD CLOSE: This action is permanent and irreversible per BIR/CAS regulations. Proceed?');">
                      @csrf
                      <button 
                        type="submit" 
                        class="inline-flex items-center gap-1 rounded-xl bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-rose-700 ring-1 ring-rose-600/20 transition-all"
                        title="Permanent Hard Close"
                      >
                        <i class="ph-bold ph-shield-check"></i>
                        <span>CFO Close (Hard)</span>
                      </button>
                    </form>
                  @else
                    <span class="inline-flex items-center gap-1 text-xs text-slate-400 font-semibold">
                      <i class="ph-bold ph-check-circle text-emerald-500"></i> Finalized
                    </span>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="py-12 text-center text-sm text-slate-400">
                <i class="ph ph-calendar-blank text-3xl mb-2 block mx-auto text-slate-300 dark:text-slate-600"></i>
                No fiscal periods initialized for FY {{ $selectedYear }}. Click "Start New Fiscal Year" to generate months.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal: Start New Fiscal Year (Alpine.js) -->
<x-modal 
  id="initYearModal" 
  title="Initialize New Fiscal Year Periods" 
  subtitle="Generates 12 monthly accounting cutoff periods for hospital operations" 
  icon="ph-calendar-plus" 
  iconVariant="emerald" 
  size="md" 
  formAction="{{ route('gl.period-end-closing.initialize') }}" 
  formMethod="POST" 
  submitText="Generate 12 Periods"
  submitIcon="ph-check"
>
  <div class="space-y-4">
    <div>
      <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Fiscal Year (YYYY) <span class="text-rose-500">*</span></label>
      <input 
        type="number" 
        name="fiscal_year" 
        value="{{ (int)date('Y') + 1 }}" 
        min="2020" 
        max="2035" 
        required 
        class="w-full rounded-xl border-0 bg-slate-50 py-2.5 px-3 text-sm font-mono font-bold text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
      >
      <span class="text-[11px] text-slate-400 mt-1 block">Creates January to December fiscal periods with default OPEN status.</span>
    </div>
  </div>
</x-modal>
@endsection

@extends('layouts.app')

@section('title', 'Executive Dashboard — Financial Management System')
@section('module', 'finance')
@section('page', 'dashboard')

@push('styles')
<style>
  /* Thin scrollbar for recent journals table */
  .slim-scroll::-webkit-scrollbar { width: 4px; height: 4px; }
  .slim-scroll::-webkit-scrollbar-track { background: transparent; }
  .slim-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
  .dark .slim-scroll::-webkit-scrollbar-thumb { background: #334155; }

  /* Pulse animation for security status dot */
  @keyframes pulse-ring {
    0%   { box-shadow: 0 0 0 0 rgba(52,211,153, 0.5); }
    70%  { box-shadow: 0 0 0 6px rgba(52,211,153, 0); }
    100% { box-shadow: 0 0 0 0 rgba(52,211,153, 0); }
  }
  .pulse-green { animation: pulse-ring 2s infinite; }

  @keyframes pulse-ring-amber {
    0%   { box-shadow: 0 0 0 0 rgba(251,191,36, 0.5); }
    70%  { box-shadow: 0 0 0 6px rgba(251,191,36, 0); }
    100% { box-shadow: 0 0 0 0 rgba(251,191,36, 0); }
  }
  .pulse-amber { animation: pulse-ring-amber 2s infinite; }

  @keyframes pulse-ring-rose {
    0%   { box-shadow: 0 0 0 0 rgba(251,113,133, 0.5); }
    70%  { box-shadow: 0 0 0 6px rgba(251,113,133, 0); }
    100% { box-shadow: 0 0 0 0 rgba(251,113,133, 0); }
  }
  .pulse-rose { animation: pulse-ring-rose 2s infinite; }
</style>
@endpush

@section('content')
<div class="space-y-5 pb-8">


  {{-- ─── TIER 1: Page Header & Live Statutory Action Buttons ────────────────── --}}
  <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div class="flex flex-wrap items-center gap-3">
      <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Executive Financial Overview</h1>

      <div class="flex flex-wrap items-center gap-2">
        {{-- Button 1: Ledger Balanced / Ledger Imbalance --}}
        <a href="{{ route('accounting.general-ledger.index') }}"
           aria-label="General Ledger: {{ $isBalanced ? 'Double-Entry In Balance' : 'Discrepancy Detected' }}"
           title="General Ledger: Sum(Debit) {{ $isBalanced ? '=' : '≠' }} Sum(Credit) across all POSTED journals"
           class="group inline-flex items-center gap-2 rounded-xl px-3 py-1.5 text-xs font-semibold shadow-xs transition-all hover:-translate-y-0.5 hover:shadow-sm
                  {{ $isBalanced
                     ? 'bg-white text-emerald-700 ring-1 ring-emerald-500/30 hover:bg-emerald-50/70 hover:ring-emerald-500/50 dark:bg-slate-900 dark:text-emerald-300 dark:ring-emerald-500/30 dark:hover:bg-emerald-950/40'
                     : 'bg-white text-rose-700 ring-1 ring-rose-500/30 hover:bg-rose-50/70 hover:ring-rose-500/50 dark:bg-slate-900 dark:text-rose-300 dark:ring-rose-500/30 dark:hover:bg-rose-950/40' }}">
          <span class="h-2 w-2 rounded-full {{ $isBalanced ? 'bg-emerald-500 pulse-green' : 'bg-rose-500 pulse-rose' }}"></span>
          <i class="ph-bold {{ $isBalanced ? 'ph-scales' : 'ph-warning-circle' }} text-sm"></i>
          <span>{{ $isBalanced ? 'Ledger Balanced' : 'Ledger Imbalance' }}</span>
          <i class="ph-bold ph-arrow-up-right text-[11px] opacity-50 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 group-hover:opacity-100"></i>
        </a>

        {{-- Button 2: COA Compliant / Remittance Overdue --}}
        <a href="{{ route('collection.bank-deposits') }}"
           aria-label="Treasury Daily Collection &amp; Deposit — COA Circular No. 2021-014"
           title="Treasury Daily Collection &amp; Deposit (COA Circular No. 2021-014) — Deposited: ₱{{ number_format($totalDeposited, 2) }}"
           class="group inline-flex items-center gap-2 rounded-xl px-3 py-1.5 text-xs font-semibold shadow-xs transition-all hover:-translate-y-0.5 hover:shadow-sm
                  {{ $isCoaIntactCompliant
                     ? 'bg-white text-teal-700 ring-1 ring-teal-500/30 hover:bg-teal-50/70 hover:ring-teal-500/50 dark:bg-slate-900 dark:text-teal-300 dark:ring-teal-500/30 dark:hover:bg-teal-950/40'
                     : 'bg-white text-amber-700 ring-1 ring-amber-500/30 hover:bg-amber-50/70 hover:ring-amber-500/50 dark:bg-slate-900 dark:text-amber-300 dark:ring-amber-500/30 dark:hover:bg-amber-950/40' }}">
          <span class="h-2 w-2 rounded-full {{ $isCoaIntactCompliant ? 'bg-teal-500 pulse-green' : 'bg-amber-500 pulse-amber' }}"></span>
          <i class="ph-bold ph-bank text-sm"></i>
          <span>{{ $isCoaIntactCompliant ? 'COA Compliant' : 'Remittance Overdue' }}</span>
          <i class="ph-bold ph-arrow-up-right text-[11px] opacity-50 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 group-hover:opacity-100"></i>
        </a>
      </div>
    </div>
    <div class="flex items-center gap-2 shrink-0">
      <a href="{{ route('accounting.reports.index') }}"
         class="inline-flex items-center gap-1.5 rounded-xl bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm ring-1 ring-slate-200 hover:bg-slate-50 dark:bg-slate-900 dark:text-slate-200 dark:ring-slate-700 dark:hover:bg-slate-800 transition-colors">
        <i class="ph-bold ph-file-text text-emerald-600 dark:text-emerald-400"></i>
        Financial Statements
      </a>
      <a href="{{ route('accounting.general-ledger.index') }}"
         class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 transition-colors">
        <i class="ph-bold ph-books"></i>
        General Ledger
      </a>
    </div>
  </div>

  {{-- ─── TIER 1: Hybrid KPI & Scorecard Stat Cards (4 cards) ───────────────── --}}
  @php
    $combinedCash = (float) $cashOnHand + (float) $cashInBank;
    $isCashNegative = $combinedCash < 0;
  @endphp
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">

    {{-- Hospital Revenue (KPI + Scorecard Margin Evaluation) --}}
    <x-stat-card
      title="Total Revenue"
      :value="$totalRevenue"
      icon="ph-trend-up"
      color="{{ $totalRevenue > 0 && $netMarginPct < 0 ? 'amber' : 'emerald' }}"
      :badge="$totalRevenue > 0 ? ($netMarginPct >= 10.0 ? 'Optimal' : ($netMarginPct >= 0.0 ? 'Narrow' : 'Deficit')) : 'Active'"
      :trend="$totalRevenue > 0 ? (($netMarginPct >= 0 ? '+' : '') . $netMarginPct . '% Margin') : null"
      :trendUp="$netMarginPct >= 0"
      subtitle="Clinical, Diagnostic & Pharmacy · Opex: ₱{{ number_format((float) $totalExpense, 2) }}"
    />

    {{-- Combined Cash Position (KPI + Scorecard Liquidity Coverage) --}}
    <x-stat-card
      title="Cash Position"
      :value="$combinedCash"
      icon="ph-vault"
      color="{{ $isCashNegative ? 'rose' : 'blue' }}"
      :badge="$isCashNegative ? 'Overdrawn' : ($hasAp ? ($liquidityCoverage >= 1.5 ? 'Strong Liquidity' : ($liquidityCoverage >= 1.0 ? 'Adequate' : 'Tight Buffer')) : '100% Liquid')"
      :trend="$isCashNegative ? 'Cash Deficit' : ($hasAp ? ($liquidityCoverage . 'x AP Coverage') : 'No Open AP')"
      :trendUp="! $isCashNegative && $liquidityCoverage >= 1.0"
      subtitle="On Hand + Land Bank AGDB · Open AP: ₱{{ number_format((float) $outstandingAP, 2) }}"
    />

    {{-- Outstanding AR (KPI + Scorecard Exposure Grading) --}}
    <x-stat-card
      title="Outstanding AR"
      :value="$outstandingAR"
      icon="ph-clock"
      color="amber"
      :badge="$outstandingAR > 0 ? 'Active Claims' : 'Fully Settled'"
      :trend="$outstandingAR > 0 ? 'Managed Risk' : 'Optimal'"
      :trendUp="true"
      subtitle="Open Patient & HMO Claims"
    />

    {{-- Undeposited Cash Collections (KPI + COA Circular 2021-014 Scorecard) --}}
    <x-stat-card
      title="Undeposited Cash"
      :value="$undepositedCollections"
      icon="{{ $isCoaIntactCompliant ? 'ph-check-circle' : 'ph-warning-circle' }}"
      color="{{ $isCoaIntactCompliant ? 'teal' : 'rose' }}"
      :badge="$isCoaIntactCompliant ? 'COA Compliant' : 'Remittance Overdue'"
      :trend="$isCoaIntactCompliant ? '≤ ₱10k Limit' : '> ₱10k Limit'"
      :trendUp="$isCoaIntactCompliant"
      subtitle="{{ $isCoaIntactCompliant ? 'COA Circular 2021-014 Remitted' : 'Action Required: Remittance Due' }} · Deposited: ₱{{ number_format((float) $totalDeposited, 2) }}"
    />

  </div>

  {{-- ─── TIER 2: Charts Row ──────────────────────────────────────────────────── --}}
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

    {{-- Chart A: Revenue vs Expense — 6-Month Trend (takes 2/3 width) --}}
    <div class="lg:col-span-2 rounded-2xl bg-white p-5 ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
      <div class="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800">
        <div>
          <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <i class="ph-bold ph-chart-line text-emerald-600 dark:text-emerald-400"></i>
            Revenue vs Operating Expenses
          </h3>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">6-month posted journal entries trend</p>
        </div>
        <div class="flex items-center gap-3 text-xs text-slate-500">
          <span class="flex items-center gap-1.5">
            <span class="h-2 w-2 rounded-full bg-emerald-500 inline-block"></span> Revenue
          </span>
          <span class="flex items-center gap-1.5">
            <span class="h-2 w-2 rounded-full bg-rose-400 inline-block"></span> Expenses
          </span>
        </div>
      </div>
      <div class="mt-4 h-52">
        <canvas id="revenueExpenseChart"></canvas>
      </div>
    </div>

    {{-- Chart B: Public Hospital Fund Sources & Universal Healthcare Co-Pay Coverage — Donut (takes 1/3 width) --}}
    <div class="rounded-2xl bg-white p-5 ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800 flex flex-col">
      <div class="border-b border-slate-100 pb-4 dark:border-slate-800">
        <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
          <i class="ph-bold ph-chart-donut text-blue-500"></i>
          Public Hospital Fund Sources &amp; Universal Healthcare Co-Pay Coverage
        </h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Current month fund-source breakdown</p>
      </div>
      <div class="flex flex-col items-center mt-4 flex-1">
        <div class="relative h-36 w-36">
          <canvas id="payerDonutChart"></canvas>
          <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
            <span class="kpi-value font-sans text-base font-bold text-slate-900 dark:text-white tabular-nums">
              ₱{{ number_format($totalFundSources, 0) }}
            </span>
            <span class="text-[10px] text-slate-400">Total</span>
          </div>
        </div>

        {{-- Legend --}}
        <div class="mt-4 w-full space-y-2">
          <div class="flex items-center justify-between text-xs">
            <span class="flex items-center gap-1.5 text-slate-600 dark:text-slate-400">
              <span class="h-2 w-2 rounded-full bg-blue-500 inline-block flex-shrink-0"></span>
              PhilHealth ACR
            </span>
            <span class="font-mono font-semibold text-slate-800 dark:text-slate-200 tabular-nums">
              <span class="text-[11px] font-normal text-slate-400 mr-1">₱{{ number_format((float) $philhealthTotal, 2) }}</span>{{ $philhealthSharePct }}%
            </span>
          </div>
          <div class="flex items-center justify-between text-xs">
            <span class="flex items-center gap-1.5 text-slate-600 dark:text-slate-400">
              <span class="h-2 w-2 rounded-full bg-teal-500 inline-block flex-shrink-0"></span>
              Malasakit Center Subsidies
            </span>
            <span class="font-mono font-semibold text-slate-800 dark:text-slate-200 tabular-nums">
              <span class="text-[11px] font-normal text-slate-400 mr-1">₱{{ number_format((float) $malasakitGlUtilized, 2) }}</span>{{ $malasakitSharePct }}%
            </span>
          </div>
          <div class="flex items-center justify-between text-xs">
            <span class="flex items-center gap-1.5 text-slate-600 dark:text-slate-400">
              <span class="h-2 w-2 rounded-full bg-sky-400 inline-block flex-shrink-0"></span>
              Private HMO
            </span>
            <span class="font-mono font-semibold text-slate-800 dark:text-slate-200 tabular-nums">
              <span class="text-[11px] font-normal text-slate-400 mr-1">₱{{ number_format((float) $hmoTotal, 2) }}</span>{{ $hmoSharePct }}%
            </span>
          </div>
          <div class="flex items-center justify-between text-xs">
            <span class="flex items-center gap-1.5 text-slate-600 dark:text-slate-400">
              <span class="h-2 w-2 rounded-full bg-emerald-400 inline-block flex-shrink-0"></span>
              Direct Cash
            </span>
            <span class="font-mono font-semibold text-slate-800 dark:text-slate-200 tabular-nums">
              <span class="text-[11px] font-normal text-slate-400 mr-1">₱{{ number_format((float) $directCopayTotal, 2) }}</span>{{ $directCopaySharePct }}%
            </span>
          </div>
        </div>
      </div>
    </div>

  </div>

  {{-- ─── TIER 2.2: Executive Hospital Financial & Operational Scorecard ─────── --}}
  <div class="rounded-2xl bg-white ring-1 ring-slate-200/80 overflow-hidden dark:bg-slate-900 dark:ring-slate-800 shadow-sm">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-800 gap-3">
      <div class="flex items-center gap-3">
        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-500/20 dark:bg-emerald-950/50 dark:text-emerald-400">
          <i class="ph-bold ph-target text-lg"></i>
        </span>
        <div>
          <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
            Executive Hospital Financial &amp; Operational Scorecard
          </h2>
          <p class="text-xs text-slate-500 dark:text-slate-400">Multi-perspective target evaluation across GAAP profitability, COA intact remittance, UHC coverage &amp; budget ceilings</p>
        </div>
      </div>

      <div class="flex items-center gap-2.5">
        <div class="text-right hidden md:block">
          <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Benchmark Index</span>
          <div class="text-xs font-semibold text-slate-700 dark:text-slate-300">
            {{ $scorecard->optimalCount }}/{{ $scorecard->totalMetrics }} Standards Met
          </div>
        </div>
        <span class="inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-bold
          @if($scorecard->compositeBadgeColor === 'emerald') bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300
          @elseif($scorecard->compositeBadgeColor === 'teal') bg-teal-50 text-teal-700 ring-1 ring-teal-600/20 dark:bg-teal-950/40 dark:text-teal-300
          @elseif($scorecard->compositeBadgeColor === 'amber') bg-amber-50 text-amber-700 ring-1 ring-amber-600/20 dark:bg-amber-950/40 dark:text-amber-300
          @else bg-rose-50 text-rose-700 ring-1 ring-rose-600/20 dark:bg-rose-950/40 dark:text-rose-300 @endif">
          <i class="ph-bold ph-medal text-sm"></i>
          <span>{{ $scorecard->compositeGrade }}</span>
          <span class="font-mono ml-0.5 tabular-nums">({{ $scorecard->compositeScorePct }}%)</span>
        </span>
      </div>
    </div>

    {{-- Scorecard Perspectives Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x divide-slate-100 dark:divide-slate-800">
      @foreach($scorecard->perspectives as $perspectiveName => $items)
        <div class="p-5 space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
              @if($perspectiveName === 'Financial Health')
                <i class="ph-bold ph-chart-line text-emerald-500"></i>
              @elseif($perspectiveName === 'Treasury & Liquidity')
                <i class="ph-bold ph-vault text-teal-500"></i>
              @elseif($perspectiveName === 'Patient Care & Universal Health')
                <i class="ph-bold ph-first-aid text-blue-500"></i>
              @else
                <i class="ph-bold ph-shield-check text-violet-500"></i>
              @endif
              {{ $perspectiveName }}
            </h3>
            <span class="text-[11px] font-mono text-slate-400">{{ count($items) }} Indicators</span>
          </div>

          <div class="space-y-3">
            @foreach($items as $scItem)
              <div class="rounded-xl bg-slate-50/70 p-3.5 ring-1 ring-slate-200/60 dark:bg-slate-800/40 dark:ring-slate-700/60 transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/60">
                <div class="flex items-start justify-between gap-2">
                  <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-1.5 flex-wrap">
                      <span class="text-xs font-bold text-slate-900 dark:text-white">{{ $scItem->metric }}</span>
                      <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-mono font-medium bg-slate-200/70 text-slate-600 dark:bg-slate-700 dark:text-slate-300 whitespace-nowrap">
                        Target: {{ $scItem->target }}
                      </span>
                    </div>
                    @if($scItem->description)
                      <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400 line-clamp-1">{{ $scItem->description }}</p>
                    @endif
                  </div>

                  <div class="text-right flex flex-col items-end flex-shrink-0">
                    <div class="flex items-baseline gap-1.5">
                      <span class="font-mono text-sm font-bold text-slate-900 dark:text-white tabular-nums">{{ $scItem->actual }}</span>
                      <span class="text-[10px] font-mono font-semibold {{ $scItem->status === 'CRITICAL' ? 'text-rose-600 dark:text-rose-400' : ($scItem->status === 'WARNING' ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400') }}">
                        ({{ $scItem->variance }})
                      </span>
                    </div>
                    <span class="mt-1 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold
                      @if($scItem->badgeColor === 'emerald') bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300
                      @elseif($scItem->badgeColor === 'teal') bg-teal-50 text-teal-700 ring-1 ring-teal-600/20 dark:bg-teal-950/40 dark:text-teal-300
                      @elseif($scItem->badgeColor === 'blue') bg-blue-50 text-blue-700 ring-1 ring-blue-600/20 dark:bg-blue-950/40 dark:text-blue-300
                      @elseif($scItem->badgeColor === 'amber') bg-amber-50 text-amber-700 ring-1 ring-amber-600/20 dark:bg-amber-950/40 dark:text-amber-300
                      @elseif($scItem->badgeColor === 'rose') bg-rose-50 text-rose-700 ring-1 ring-rose-600/20 dark:bg-rose-950/40 dark:text-rose-300
                      @else bg-slate-100 text-slate-700 ring-1 ring-slate-200 dark:bg-slate-800 dark:text-slate-300 @endif">
                      <i class="ph-bold {{ $scItem->icon }} text-[10px]"></i>
                      {{ $scItem->statusLabel }}
                    </span>
                  </div>
                </div>

                {{-- Progress Bar --}}
                @if($scItem->progressPct !== null)
                  <div class="mt-2.5 h-1.5 w-full rounded-full bg-slate-200/80 dark:bg-slate-700 overflow-hidden">
                    <div class="h-full rounded-full transition-all
                      @if($scItem->badgeColor === 'emerald') bg-emerald-500
                      @elseif($scItem->badgeColor === 'teal') bg-teal-500
                      @elseif($scItem->badgeColor === 'blue') bg-blue-500
                      @elseif($scItem->badgeColor === 'amber') bg-amber-500
                      @elseif($scItem->badgeColor === 'rose') bg-rose-500
                      @else bg-slate-400 @endif"
                      style="width: {{ min(100.0, max(0.0, $scItem->progressPct)) }}%">
                    </div>
                  </div>
                @endif
              </div>
            @endforeach
          </div>
        </div>
      @endforeach
    </div>
  </div>

  {{-- ─── TIER 3: Operational Ledger, Departmental Budgets & Security Grid ─────── --}}
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">

    {{-- Left Column (7/12): Recent Journal Postings --}}
    <div class="lg:col-span-7 rounded-2xl bg-white ring-1 ring-slate-200/80 overflow-hidden dark:bg-slate-900 dark:ring-slate-800 shadow-sm">
      <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-800">
        <div class="flex items-center gap-2">
          <i class="ph-bold ph-clock-counter-clockwise text-emerald-600 dark:text-emerald-400 text-base"></i>
          <h3 class="text-sm font-bold text-slate-900 dark:text-white">Recent Journal Postings</h3>
          @if($isBalanced)
            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300">
              <i class="ph-bold ph-check-circle"></i> Balanced
            </span>
          @else
            <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-bold text-rose-700 ring-1 ring-rose-600/20 dark:bg-rose-950/40 dark:text-rose-300">
              <i class="ph-bold ph-warning-circle"></i> Discrepancy
            </span>
          @endif
        </div>
        <a href="{{ route('accounting.general-ledger.index') }}"
           class="inline-flex items-center gap-1 text-xs font-semibold text-slate-500 hover:text-slate-800 dark:hover:text-white transition-colors">
          View All <i class="ph-bold ph-arrow-right text-[10px]"></i>
        </a>
      </div>

      <div class="overflow-x-auto slim-scroll">
        <table class="w-full text-left text-sm text-slate-700 dark:text-slate-300">
          <thead class="border-b border-slate-100 bg-slate-50/60 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:border-slate-800 dark:bg-slate-800/40 dark:text-slate-500">
            <tr>
              <th class="py-2.5 px-4">Reference</th>
              <th class="py-2.5 px-4">Date</th>
              <th class="py-2.5 px-4">Description</th>
              <th class="py-2.5 px-4">Type</th>
              <th class="py-2.5 px-4 text-center">Status</th>
              <th class="py-2.5 px-4 text-right font-mono">Amount (Dr)</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50 dark:divide-slate-800/80">
            @forelse($recentJournals as $je)
              <tr class="transition-colors hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                <td class="py-3 px-4 font-mono text-xs font-semibold text-slate-900 dark:text-white whitespace-nowrap">
                  {{ $je->reference_number }}
                </td>
                <td class="py-3 px-4 text-xs text-slate-500 dark:text-slate-400 font-mono whitespace-nowrap">
                  {{ $je->entry_date->format('M d, Y') }}
                </td>
                <td class="py-3 px-4 text-xs text-slate-700 dark:text-slate-300 max-w-xs truncate">
                  {{ $je->description }}
                </td>
                <td class="py-3 px-4">
                  <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[11px] font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                    {{ $je->type }}
                  </span>
                </td>
                <td class="py-3 px-4 text-center">
                  <x-status-badge :status="$je->status" />
                </td>
                <td class="py-3 px-4 text-right font-mono text-xs font-bold text-slate-900 dark:text-white tabular-nums whitespace-nowrap">
                  ₱{{ number_format((float) $je->lines->sum('debit'), 2) }}
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="py-10 text-center text-sm text-slate-400">
                  <i class="ph ph-receipt text-3xl block mb-2 text-slate-300"></i>
                  No journal transactions recorded yet.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    {{-- Right Column (5/12): Departmental Budgets + Security & Audit Telemetry --}}
    <div class="lg:col-span-5 space-y-5">

      {{-- Departmental Fiscal Budget & Expenditure Burn Rate (FY 2026) --}}
      <div class="rounded-2xl bg-white ring-1 ring-slate-200/80 overflow-hidden dark:bg-slate-900 dark:ring-slate-800 shadow-sm">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-800">
          <div class="flex items-center gap-2">
            <i class="ph-bold ph-chart-bar text-violet-600 dark:text-violet-400 text-base"></i>
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Departmental Fiscal Budget &amp; Expenditure Burn Rate (FY 2026)</h3>
          </div>
          <span class="inline-flex items-center gap-1.5 text-xs font-mono font-semibold text-slate-600 dark:text-slate-300 shrink-0">
            Overall: <span class="font-bold text-violet-700 dark:text-violet-400">{{ $overallBurnRate }}% Spent</span>
          </span>
        </div>

        @if($criticalBudgets->count() > 0)
          <div class="mx-5 mt-4 mb-1 rounded-xl bg-amber-50 ring-1 ring-amber-300/60 px-4 py-3 dark:bg-amber-950/30 dark:ring-amber-800/50">
            <p class="flex items-center gap-2 text-xs font-semibold text-amber-800 dark:text-amber-300">
              <i class="ph-bold ph-warning text-base"></i>
              Approaching Budget Exhaustion — {{ $criticalBudgets->count() }} department{{ $criticalBudgets->count() !== 1 ? 's' : '' }} above 85% threshold
            </p>
          </div>
        @endif

        @php
          $displayBudgets = $criticalBudgets->isNotEmpty()
              ? $criticalBudgets
              : $budgetAllocations->take(4);
        @endphp

        <div class="px-5 pb-5 pt-3.5 space-y-3">
          @foreach($displayBudgets as $budget)
            @php
              $burnPct = bccomp((string) $budget->allocated_amount, '0.0000', 4) > 0
                ? number_format(
                    round((float) bcdiv((string) $budget->spent_amount, (string) $budget->allocated_amount, 4) * 100, 1),
                    1
                  )
                : '0.0';
              $burnVal = (float) $burnPct;
              $barColor = $burnVal >= 95 ? 'bg-rose-500' : ($burnVal >= 85 ? 'bg-amber-500' : 'bg-emerald-500');
              $txtColor = $burnVal >= 95 ? 'text-rose-600 dark:text-rose-400' : ($burnVal >= 85 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400');
            @endphp
            <div class="space-y-1">
              <div class="flex items-center justify-between gap-2 text-xs">
                <span class="font-semibold text-slate-800 dark:text-slate-100 truncate">{{ $budget->department }}</span>
                <span class="font-mono font-bold {{ $txtColor }} whitespace-nowrap">{{ $burnPct }}% Spent</span>
              </div>
              <div class="h-1.5 w-full rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                <div class="h-full rounded-full {{ $barColor }} transition-all"
                     style="width: {{ min(max($burnVal, 2), 100) }}%">
                </div>
              </div>
              <div class="flex items-center justify-between text-[10px] font-mono text-slate-400">
                <span>Spent: ₱{{ number_format((float) $budget->spent_amount, 2) }}</span>
                <span>Allocated: ₱{{ number_format((float) $budget->allocated_amount, 2) }}</span>
              </div>
            </div>
          @endforeach

          @if($criticalBudgets->isEmpty())
            <div class="flex items-center justify-between rounded-xl bg-emerald-50/60 dark:bg-emerald-950/20 px-3.5 py-2.5 text-xs text-emerald-700 dark:text-emerald-300 ring-1 ring-emerald-500/20">
              <span class="flex items-center gap-1.5 font-medium">
                <i class="ph-bold ph-check-circle text-emerald-600 dark:text-emerald-400"></i>
                All departmental budgets are within healthy thresholds.
              </span>
              <a href="{{ route('budget.variance-analysis') }}" class="font-semibold hover:underline whitespace-nowrap ml-2">
                View Budgets &rarr;
              </a>
            </div>
          @endif
        </div>
      </div>

      {{-- Security & Audit Trail Telemetry --}}
      <div class="rounded-2xl bg-white ring-1 ring-slate-200/80 overflow-hidden dark:bg-slate-900 dark:ring-slate-800 shadow-sm">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-800">
          <div class="flex items-center gap-2">
            <i class="ph-bold ph-shield-check text-rose-500 text-base"></i>
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Security &amp; Audit Trail Telemetry</h3>
            @if($failedLoginsToday > 0)
              <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-bold text-rose-700 ring-1 ring-rose-600/20 dark:bg-rose-950/40 dark:text-rose-300">
                <i class="ph-bold ph-warning"></i> {{ $failedLoginsToday }} Alerts
              </span>
            @else
              <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300">
                <i class="ph-bold ph-check-circle"></i> Secure
              </span>
            @endif
          </div>
          <a href="{{ route('accounting.audit-log') }}"
             class="inline-flex items-center gap-1 text-xs font-semibold text-slate-500 hover:text-slate-800 dark:hover:text-white transition-colors">
            Audit Hub <i class="ph-bold ph-arrow-right text-[10px]"></i>
          </a>
        </div>

        <div class="grid grid-cols-3 divide-x divide-slate-100 dark:divide-slate-800">
          {{-- Stat: Failed Logins Today --}}
          <div class="px-4 py-3.5">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Failed Logins Today</p>
            <p class="mt-1 text-xl font-bold font-mono {{ $failedLoginsToday > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-800 dark:text-slate-100' }}">
              {{ $failedLoginsToday }}
            </p>
          </div>
          {{-- Stat: Mutations Today --}}
          <div class="px-4 py-3.5">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Ledger Mutations Today</p>
            <p class="mt-1 text-xl font-bold font-mono text-slate-800 dark:text-slate-100">{{ $mutationsToday }}</p>
          </div>
          {{-- Stat: Workstation Status --}}
          <div class="px-4 py-3.5">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Terminal Status</p>
            <p class="mt-1 text-xs font-semibold {{ $securityAlertLevel === 0 ? 'text-emerald-600 dark:text-emerald-400' : ($securityAlertLevel === 1 ? 'text-amber-600 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400') }}">
              {{ $securityAlertLevel === 0 ? '✓ All Clear' : ($securityAlertLevel === 1 ? '⚠ Warning' : '⛔ Alert') }}
            </p>
          </div>
        </div>

        {{-- Recent Audit Log Entries --}}
        @if($recentAuditLogs->isNotEmpty())
          <div class="border-t border-slate-100 dark:border-slate-800 px-5 pb-4 pt-3">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2">Recent Activity</p>
            <div class="space-y-1.5">
              @foreach($recentAuditLogs as $log)
                <div class="flex items-center justify-between text-xs text-slate-600 dark:text-slate-300">
                  <span class="flex items-center gap-2 min-w-0">
                    <span class="inline-block h-1.5 w-1.5 shrink-0 rounded-full {{ $log->event === 'failed_login' ? 'bg-rose-500' : 'bg-emerald-500' }}"></span>
                    <span class="font-medium shrink-0">{{ $log->event }}</span>
                    @if($log->description)
                      <span class="text-slate-400 truncate">— {{ $log->description }}</span>
                    @endif
                  </span>
                  <span class="font-mono text-slate-400 text-[10px] whitespace-nowrap ml-2 shrink-0">
                    {{ $log->ip_address ?? '—' }}
                  </span>
                </div>
              @endforeach
            </div>
          </div>
        @endif
      </div>

    </div>

  </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

  const isDark = document.documentElement.classList.contains('dark');

  const gridColor   = isDark ? 'rgba(51,65,85,0.5)'  : 'rgba(226,232,240,0.8)';
  const labelColor  = isDark ? '#94a3b8' : '#64748b';
  const tooltipBg   = isDark ? '#1e293b' : '#ffffff';
  const tooltipBorder = isDark ? '#334155' : '#e2e8f0';

  Chart.defaults.font.family = "'Inter', sans-serif";

  // ── Chart A: Revenue vs Expense (Area Line Chart) ──────────────────────────
  const revCtx = document.getElementById('revenueExpenseChart');
  if (revCtx) {
    const months  = {!! $chartMonths !!};
    const revenue = {!! $chartRevenue !!};
    const expense = {!! $chartExpense !!};

    new Chart(revCtx, {
      type: 'line',
      data: {
        labels: months,
        datasets: [
          {
            label: 'Revenue',
            data: revenue,
            borderColor: '#10b981',
            backgroundColor: 'rgba(16,185,129,0.08)',
            borderWidth: 2,
            pointRadius: 3,
            pointHoverRadius: 5,
            pointBackgroundColor: '#10b981',
            tension: 0.4,
            fill: true,
          },
          {
            label: 'Expenses',
            data: expense,
            borderColor: '#fb7185',
            backgroundColor: 'rgba(251,113,133,0.06)',
            borderWidth: 2,
            pointRadius: 3,
            pointHoverRadius: 5,
            pointBackgroundColor: '#fb7185',
            tension: 0.4,
            fill: true,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: tooltipBg,
            borderColor: tooltipBorder,
            borderWidth: 1,
            titleColor: isDark ? '#e2e8f0' : '#0f172a',
            bodyColor: labelColor,
            padding: 10,
            callbacks: {
              label: ctx => ` ${ctx.dataset.label}: ₱${Number(ctx.raw).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`,
            },
          },
        },
        scales: {
          x: {
            grid: { color: gridColor },
            ticks: { color: labelColor, font: { size: 11 } },
          },
          y: {
            grid: { color: gridColor },
            ticks: {
              color: labelColor,
              font: { size: 11 },
              callback: val => '₱' + Number(val).toLocaleString('en-PH', { notation: 'compact', maximumFractionDigits: 1 }),
            },
            beginAtZero: true,
          },
        },
      },
    });
  }

  // ── Chart B: Payer Source Donut ───────────────────────────────────────────
  const donutCtx = document.getElementById('payerDonutChart');
  if (donutCtx) {
    const labels = {!! $payerLabels !!};
    const values = {!! $payerValues !!};

    new Chart(donutCtx, {
      type: 'doughnut',
      data: {
        labels,
        datasets: [{
          data: values,
          backgroundColor: ['#3b82f6', '#14b8a6', '#38bdf8', '#34d399'],
          borderColor: isDark ? '#0f172a' : '#ffffff',
          borderWidth: 3,
          hoverOffset: 4,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '72%',
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: tooltipBg,
            borderColor: tooltipBorder,
            borderWidth: 1,
            titleColor: isDark ? '#e2e8f0' : '#0f172a',
            bodyColor: labelColor,
            padding: 10,
            callbacks: {
              label: ctx => ` ${ctx.label}: ${ctx.raw}%`,
            },
          },
        },
      },
    });
  }

});
</script>
@endpush

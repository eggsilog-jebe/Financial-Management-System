@extends('layouts.app')

@section('title', 'Payable Aging Schedule - Accounts Payable | FMS')
@section('module', 'ap')
@section('page', 'payable-aging')

@section('content')
<div class="space-y-6">
  <!-- Executive Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
    <div>
      <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
          Accounts Payable Aging Schedule
        </h1>
    </div>

    <div class="flex items-center gap-2.5 flex-wrap">
      <button 
        type="button" 
        onclick="window.print()" 
        class="inline-flex items-center gap-1.5 rounded-xl bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-700 transition-all"
      >
        <i class="ph-bold ph-printer"></i>
        <span>Print Schedule</span>
      </button>
      <a 
        href="{{ route('ap.payable-aging.export', ['as_of_date' => $asOfDate, 'aging_basis' => $agingBasis ?? 'due_date']) }}" 
        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 ring-1 ring-emerald-600/20 transition-all"
      >
        <i class="ph-bold ph-download-simple"></i>
        <span>Export Aging CSV</span>
      </a>
    </div>
  </div>

  <!-- Aging Buckets (5-Tier + Grand Total) -->
  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
    <x-stat-card 
      title="Current (0-30d)" 
      :value="$totalCurrent" 
      icon="ph-check-circle" 
      color="emerald" 
      subtitle="Within credit terms"
    />

    <x-stat-card 
      title="1 - 30 Days" 
      :value="$total1To30" 
      icon="ph-clock" 
      color="blue" 
      subtitle="Early overdue"
    />

    <x-stat-card 
      title="31 - 60 Days" 
      :value="$total31To60" 
      icon="ph-hourglass" 
      color="amber" 
      subtitle="Notice received"
    />

    <x-stat-card 
      title="61 - 90 Days" 
      :value="$total61To90" 
      icon="ph-warning" 
      color="amber" 
      subtitle="Urgent settlement"
    />

    <x-stat-card 
      title="> 90 Days" 
      :value="$total90Plus" 
      icon="ph-shield-warning" 
      color="rose" 
      badge="Critical"
      subtitle="Critical overdue"
    />

    <x-stat-card 
      title="Grand Total AP" 
      :value="$grandTotalPayable" 
      icon="ph-trend-down" 
      color="slate" 
      subtitle="All aging brackets"
    />
  </div>

  <!-- Aging Schedule Table Card -->
  
  <!-- Sub-Module Category Navigation Bar -->
  @include('partials.submodule-nav')

  <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 overflow-hidden dark:bg-slate-900 dark:ring-slate-800">
    <!-- Filter Toolbar -->
    <div class="border-b border-slate-200 p-4 dark:border-slate-800">
      <form method="GET" action="{{ route('ap.payable-aging') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3.5 items-end">
        <div class="sm:col-span-3">
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">As-Of Cutoff Date:</label>
          <input 
            type="date" 
            name="as_of_date" 
            value="{{ $asOfDate }}" 
            onchange="this.form.submit()" 
            class="w-full rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
          >
        </div>

        <div class="sm:col-span-3">
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Aging Calculated By:</label>
          <select 
            name="aging_basis" 
            onchange="this.form.submit()" 
            class="w-full rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
          >
            <option value="due_date" {{ ($agingBasis ?? 'due_date') === 'due_date' ? 'selected' : '' }}>Due Date (Default)</option>
            <option value="invoice_date" {{ ($agingBasis ?? '') === 'invoice_date' || ($agingBasis ?? '') === 'bill_date' ? 'selected' : '' }}>Bill Date (Invoice)</option>
            <option value="bill_date" {{ ($agingBasis ?? '') === 'bill_date' ? 'selected' : '' }}>Bill Date (Invoice)</option>
          </select>
        </div>

        <div class="sm:col-span-4">
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Search Vendor:</label>
          <div class="relative w-full">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
              <i class="ph ph-magnifying-glass text-sm"></i>
            </div>
            <input 
              type="search" 
              name="search" 
              value="{{ request('search') }}" 
              placeholder="Search vendor code or name..." 
              class="w-full rounded-xl border-0 bg-slate-50 py-1.5 pl-9 pr-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
            >
          </div>
        </div>

        <div class="sm:col-span-2">
          <a 
            href="{{ route('ap.payable-aging') }}" 
            class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl bg-slate-100 py-1.5 px-3 text-xs font-semibold text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 transition-colors"
          >
            <i class="ph-bold ph-arrow-counter-clockwise"></i>
            <span>Reset</span>
          </a>
        </div>
      </form>
    </div>

    <div class="border-b border-slate-200 px-5 py-3 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-900/50">
      <div>
        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Vendor Aging Breakdown</h3>
        <p class="text-xs text-slate-500 dark:text-slate-400">Detailed liabilities per accredited supplier</p>
      </div>
      @php
        $agingRows = $vendors ?? $agingData ?? [];
      @endphp
      <span class="text-xs font-mono font-semibold text-slate-500">{{ count($agingRows) }} Suppliers with Open Balances</span>
    </div>

    <div class="overflow-x-auto custom-scrollbar">
      <table class="w-full text-left text-sm text-slate-700 dark:text-slate-300">
        <thead class="border-b border-slate-200 bg-slate-50/75 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-400">
          <tr>
            <th scope="col" class="py-3.5 pl-5 pr-3 w-28">Vendor Code</th>
            <th scope="col" class="px-3 py-3.5">Supplier Name</th>
            <th scope="col" class="px-3 py-3.5 text-right font-mono">Current</th>
            <th scope="col" class="px-3 py-3.5 text-right font-mono">1-30 Days</th>
            <th scope="col" class="px-3 py-3.5 text-right font-mono">31-60 Days</th>
            <th scope="col" class="px-3 py-3.5 text-right font-mono">61-90 Days</th>
            <th scope="col" class="px-3 py-3.5 text-right font-mono">&gt; 90 Days</th>
            <th scope="col" class="px-3 py-3.5 text-right font-mono">Total Payable</th>
            <th scope="col" class="py-3.5 pl-3 pr-5 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-mono text-xs">
          @forelse($agingRows as $item)
            <tr class="transition-colors hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
              <td class="py-3.5 pl-5 pr-3 font-bold text-slate-900 dark:text-white">
                <span class="rounded-lg bg-slate-100 px-2 py-0.5 font-mono text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                  {{ $item['vendor_code'] }}
                </span>
              </td>
              <td class="px-3 py-3.5 font-sans font-semibold text-slate-900 dark:text-white">
                {{ $item['vendor_name'] }}
              </td>
              <td class="px-3 py-3.5 text-right tabular-nums {{ (float)$item['current'] > 0 ? 'font-bold text-emerald-600 dark:text-emerald-400' : 'text-slate-400' }}">
                {{ (float)$item['current'] > 0 ? '₱' . number_format((float)$item['current'], 2) : '—' }}
              </td>
              <td class="px-3 py-3.5 text-right tabular-nums {{ (float)$item['days_1_30'] > 0 ? 'font-bold text-blue-600 dark:text-blue-400' : 'text-slate-400' }}">
                {{ (float)$item['days_1_30'] > 0 ? '₱' . number_format((float)$item['days_1_30'], 2) : '—' }}
              </td>
              <td class="px-3 py-3.5 text-right tabular-nums {{ (float)$item['days_31_60'] > 0 ? 'font-bold text-amber-600 dark:text-amber-400' : 'text-slate-400' }}">
                {{ (float)$item['days_31_60'] > 0 ? '₱' . number_format((float)$item['days_31_60'], 2) : '—' }}
              </td>
              <td class="px-3 py-3.5 text-right tabular-nums {{ (float)$item['days_61_90'] > 0 ? 'font-bold text-orange-600 dark:text-orange-400' : 'text-slate-400' }}">
                {{ (float)$item['days_61_90'] > 0 ? '₱' . number_format((float)$item['days_61_90'], 2) : '—' }}
              </td>
              <td class="px-3 py-3.5 text-right tabular-nums {{ (float)$item['days_90_plus'] > 0 ? 'font-bold text-rose-600 dark:text-rose-400' : 'text-slate-400' }}">
                {{ (float)$item['days_90_plus'] > 0 ? '₱' . number_format((float)$item['days_90_plus'], 2) : '—' }}
              </td>
              <td class="px-3 py-3.5 text-right font-bold text-slate-900 dark:text-white tabular-nums">
                ₱{{ number_format((float)($item['total_due'] ?? $item['total_payable'] ?? 0), 2) }}
              </td>
              <td class="py-3.5 pl-3 pr-5 text-right font-sans">
                <a 
                  href="{{ route('ap.invoices', ['vendor_id' => $item['vendor_id']]) }}" 
                  class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 transition-colors"
                  title="View Breakdown"
                >
                  <i class="ph-bold ph-receipt"></i>
                  <span>View Breakdown</span>
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="py-12 text-center text-sm font-sans text-slate-400">
                <i class="ph ph-check-circle text-3xl mb-2 block mx-auto text-emerald-500"></i>
                Zero outstanding vendor liabilities detected as of {{ $asOfDate }}.
              </td>
            </tr>
          @endforelse
        </tbody>
        <tfoot class="border-t-2 border-slate-300 bg-slate-50 dark:border-slate-700 dark:bg-slate-900/60 font-mono text-xs font-bold">
          <tr>
            <td colspan="2" class="py-4 pl-5 pr-3 font-sans text-right text-slate-700 dark:text-slate-300 uppercase">
              Schedule Grand Total:
            </td>
            <td class="px-3 py-4 text-right text-emerald-600 dark:text-emerald-400 tabular-nums">
              ₱{{ number_format((float)$totalCurrent, 2) }}
            </td>
            <td class="px-3 py-4 text-right text-blue-600 dark:text-blue-400 tabular-nums">
              ₱{{ number_format((float)$total1To30, 2) }}
            </td>
            <td class="px-3 py-4 text-right text-amber-600 dark:text-amber-400 tabular-nums">
              ₱{{ number_format((float)$total31To60, 2) }}
            </td>
            <td class="px-3 py-4 text-right text-orange-600 dark:text-orange-400 tabular-nums">
              ₱{{ number_format((float)$total61To90, 2) }}
            </td>
            <td class="px-3 py-4 text-right text-rose-600 dark:text-rose-400 tabular-nums">
              ₱{{ number_format((float)$total90Plus, 2) }}
            </td>
            <td class="px-3 py-4 text-right text-slate-900 dark:text-white tabular-nums text-sm">
              ₱{{ number_format((float)$grandTotalPayable, 2) }}
            </td>
            <td class="py-4 pl-3 pr-5"></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>
@endsection

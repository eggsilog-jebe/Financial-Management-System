@extends('layouts.app')

@section('title', 'Fiscal Period-End Closing & Hard Locking')
@section('module', 'general-ledger')
@section('page', 'period-close')

@section('content')
<div class="space-y-6">

  {{-- Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <div class="flex items-center gap-2 text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">
        <a href="{{ route('accounting.dashboard') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">Overview</a>
        <i class="ph ph-caret-right text-[10px]"></i>
        <a href="{{ route('accounting.general-ledger.index') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">General Ledger</a>
        <i class="ph ph-caret-right text-[10px]"></i>
        <span class="text-slate-900 dark:text-slate-200 font-semibold">Period Closing</span>
      </div>
      <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
        Fiscal Period Closing &amp; Hard Locking
      </h1>
    </div>
    <div class="flex flex-wrap items-center gap-2.5">
      <span class="inline-flex items-center gap-1.5 rounded-xl border border-rose-200/80 bg-rose-50/80 px-3 py-1.5 text-xs font-semibold text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-300">
        <i class="ph-fill ph-lock-key text-rose-500"></i>
        CFO Exclusive Area
      </span>
      <a href="{{ route('accounting.dashboard') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 transition-all">
        <i class="ph ph-arrow-left"></i>
        Dashboard
      </a>
    </div>
  </div>

  @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-transition class="flex items-center justify-between gap-3 rounded-2xl border border-emerald-200/80 bg-emerald-50/80 p-4 text-xs text-emerald-800 shadow-sm dark:border-emerald-800/40 dark:bg-emerald-950/40 dark:text-emerald-300">
      <div class="flex items-center gap-2.5">
        <i class="ph-fill ph-check-circle text-lg text-emerald-600 dark:text-emerald-400 shrink-0"></i>
        <span>{{ session('success') }}</span>
      </div>
      <button @click="show = false" type="button" class="text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-200 transition-colors">
        <i class="ph ph-x text-sm"></i>
      </button>
    </div>
  @endif


  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    {{-- Active Fiscal Period Status Card --}}
    <div class="lg:col-span-5">
      <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800 h-full flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
              <i class="ph ph-calendar-check text-emerald-600 dark:text-emerald-400"></i>
              Active Fiscal Cycle
            </h3>
            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-700/10 dark:bg-emerald-950/60 dark:text-emerald-400 dark:ring-emerald-500/20">
              <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
              OPEN FOR POSTING
            </span>
          </div>

          <div class="my-5 rounded-xl border border-slate-100 bg-slate-50/75 p-4 space-y-3 dark:border-slate-800 dark:bg-slate-950/60">
            <div class="flex items-center justify-between text-xs">
              <span class="text-slate-500 dark:text-slate-400">Current Period:</span>
              <span class="font-bold text-slate-900 dark:text-white">{{ $activePeriod }}</span>
            </div>
            <div class="flex items-center justify-between text-xs">
              <span class="text-slate-500 dark:text-slate-400">Total Journal Entries:</span>
              <span class="font-bold font-mono text-slate-900 dark:text-white">{{ number_format($totalEntriesCount) }} Transactions</span>
            </div>
            <div class="flex items-center justify-between text-xs">
              <span class="text-slate-500 dark:text-slate-400">Pending Unposted Drafts:</span>
              <span class="font-bold font-mono text-amber-600 dark:text-amber-400">{{ number_format($unpostedEntriesCount) }} Drafts</span>
            </div>
          </div>

          <div class="rounded-xl border border-amber-200/80 bg-amber-50/75 p-4 text-xs text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-200">
            <h4 class="font-bold flex items-center gap-1.5 mb-2 text-amber-950 dark:text-amber-100">
              <i class="ph-fill ph-warning-circle text-amber-600 dark:text-amber-400 text-sm"></i>
              Period Hard Locking Rules:
            </h4>
            <ul class="space-y-1.5 pl-4 list-disc text-[11px] text-amber-800 dark:text-amber-300">
              <li>Locks the General Ledger against all past-dated postings, adjustments, or reversals.</li>
              <li>Enforces strict double-entry immutability under Philippine BIR CAS regulations.</li>
              <li>Reopening a locked period requires external BIR CAS audit re-authorization and formal board resolution.</li>
            </ul>
          </div>
        </div>

        <form method="POST" action="{{ route('accounting.period-close.lock') }}" onsubmit="return confirm('Are you sure you want to hard lock this fiscal period? This action cannot be reversed without CFO override.');" class="pt-6">
          @csrf
          <input type="hidden" name="period_name" value="{{ $activePeriod }}">
          <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-rose-600 py-2.5 px-4 text-xs font-semibold text-white shadow-sm hover:bg-rose-700 transition-all">
            <i class="ph ph-lock-key text-base"></i>
            Execute Fiscal Hard Lock (Close Period)
          </button>
        </form>
      </div>
    </div>

    {{-- Fiscal Period History --}}
    <div class="lg:col-span-7">
      <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800 overflow-hidden h-full">
        <div class="p-5 border-b border-slate-100 dark:border-slate-800">
          <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <i class="ph ph-clock-counter-clockwise text-slate-400"></i>
            Fiscal Period History &amp; Audit Logs
          </h3>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Historical monthly and annual closing records with immutable timestamps.</p>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="border-b border-slate-200/80 bg-slate-50/75 dark:border-slate-800 dark:bg-slate-950/50">
                <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Period Code</th>
                <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Closing Date</th>
                <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Closed By</th>
                <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px] text-right">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
              <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                <td class="py-3.5 px-4 font-mono font-semibold text-slate-900 dark:text-white">FY-2026-07</td>
                <td class="py-3.5 px-4 text-slate-600 dark:text-slate-300">Jul 31, 2026</td>
                <td class="py-3.5 px-4 text-slate-600 dark:text-slate-300">Chief Financial Officer (CFO)</td>
                <td class="py-3.5 px-4 text-right">
                  <span class="inline-flex items-center gap-1 rounded-md bg-rose-50 px-2 py-0.5 text-[11px] font-semibold text-rose-700 ring-1 ring-inset ring-rose-700/10 dark:bg-rose-950/60 dark:text-rose-400 dark:ring-rose-500/20">
                    <i class="ph ph-lock text-[10px]"></i>
                    LOCKED
                  </span>
                </td>
              </tr>
              <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                <td class="py-3.5 px-4 font-mono font-semibold text-slate-900 dark:text-white">FY-2026-06</td>
                <td class="py-3.5 px-4 text-slate-600 dark:text-slate-300">Jun 30, 2026</td>
                <td class="py-3.5 px-4 text-slate-600 dark:text-slate-300">Chief Financial Officer (CFO)</td>
                <td class="py-3.5 px-4 text-right">
                  <span class="inline-flex items-center gap-1 rounded-md bg-rose-50 px-2 py-0.5 text-[11px] font-semibold text-rose-700 ring-1 ring-inset ring-rose-700/10 dark:bg-rose-950/60 dark:text-rose-400 dark:ring-rose-500/20">
                    <i class="ph ph-lock text-[10px]"></i>
                    LOCKED
                  </span>
                </td>
              </tr>
              <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                <td class="py-3.5 px-4 font-mono font-semibold text-slate-900 dark:text-white">FY-2026-05</td>
                <td class="py-3.5 px-4 text-slate-600 dark:text-slate-300">May 31, 2026</td>
                <td class="py-3.5 px-4 text-slate-600 dark:text-slate-300">Chief Financial Officer (CFO)</td>
                <td class="py-3.5 px-4 text-right">
                  <span class="inline-flex items-center gap-1 rounded-md bg-rose-50 px-2 py-0.5 text-[11px] font-semibold text-rose-700 ring-1 ring-inset ring-rose-700/10 dark:bg-rose-950/60 dark:text-rose-400 dark:ring-rose-500/20">
                    <i class="ph ph-lock text-[10px]"></i>
                    LOCKED
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection

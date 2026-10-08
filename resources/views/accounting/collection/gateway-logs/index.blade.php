@extends('layouts.app')

@section('title', 'Payment Gateway Logs - Collection Management | FMS')
@section('module', 'collection')
@section('page', 'payment-gateways')

@section('content')
<div class="space-y-6">

  {{-- Page Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <div class="flex items-center gap-2 text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">
        <a href="{{ route('accounting.dashboard') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">Overview</a>
        <i class="ph ph-caret-right text-[10px]"></i>
        <a href="{{ route('collection.receipts') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">Collection</a>
        <i class="ph ph-caret-right text-[10px]"></i>
        <span class="text-slate-900 dark:text-slate-200 font-semibold">Gateway Logs</span>
      </div>
      <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
        Payment Gateway &amp; E-Wallet Logs
      </h1>
    </div>
    <div class="flex flex-wrap items-center gap-2.5">
      <a href="{{ route('collection.cashier-desk') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 transition-all">
        <i class="ph ph-hand-coins"></i>
        Cashier POS Desk
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

  @if(session('error'))
    <div x-data="{ show: true }" x-show="show" x-transition class="flex items-center justify-between gap-3 rounded-2xl border border-rose-200/80 bg-rose-50/80 p-4 text-xs text-rose-800 shadow-sm dark:border-rose-800/40 dark:bg-rose-950/40 dark:text-rose-300">
      <div class="flex items-center gap-2.5">
        <i class="ph-fill ph-warning-circle text-lg text-rose-600 dark:text-rose-400 shrink-0"></i>
        <span>{{ session('error') }}</span>
      </div>
      <button @click="show = false" type="button" class="text-rose-600 hover:text-rose-800 dark:text-rose-400 dark:hover:text-rose-200 transition-colors">
        <i class="ph ph-x text-sm"></i>
      </button>
    </div>
  @endif

  {{-- Metric Summary Cards --}}
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    {{-- Inflows --}}
    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
      <div class="flex items-center justify-between">
        <span class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Digital Gateway Inflows</span>
        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
          <i class="ph-duotone ph-globe-hemisphere-west text-lg"></i>
        </div>
      </div>
      <div class="mt-2">
        <span class="text-2xl font-bold font-mono text-blue-600 dark:text-blue-400">₱{{ number_format((float) ($totalOnline ?? 0), 2) }}</span>
      </div>
      <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Settled through online rails</p>
    </div>

    {{-- Transactions Count --}}
    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
      <div class="flex items-center justify-between">
        <span class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Digital Transactions</span>
        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
          <i class="ph-duotone ph-credit-card text-lg"></i>
        </div>
      </div>
      <div class="mt-2">
        <span class="text-2xl font-bold font-mono text-slate-900 dark:text-white">{{ count($logs ?? []) }}</span>
      </div>
      <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Successful API webhook events</p>
    </div>

    {{-- Settlement Status --}}
    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
      <div class="flex items-center justify-between">
        <span class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Settlement Status</span>
        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-cyan-50 text-cyan-600 dark:bg-cyan-950/60 dark:text-cyan-400">
          <i class="ph-duotone ph-shield-check text-lg"></i>
        </div>
      </div>
      <div class="mt-2">
        <span class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
          <i class="ph-fill ph-check-circle text-xl"></i>
          100% Synced
        </span>
      </div>
      <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">All gateway tenders reconciled</p>
    </div>
  </div>


{{-- Data Table Card --}}
  <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800 overflow-hidden">
    <div class="p-5 border-b border-slate-100 dark:border-slate-800">
      <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
        <i class="ph ph-device-mobile text-blue-600 dark:text-blue-400"></i>
        Digital Gateway &amp; POS Settlements Stream
      </h3>
      <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Live webhook transaction feed from e-wallets, credit/debit cards, and payment aggregators.</p>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse text-xs">
        <thead>
          <tr class="border-b border-slate-200/80 bg-slate-50/75 dark:border-slate-800 dark:bg-slate-950/50">
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Channel / Provider</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Transaction Channel Ref</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Official Receipt #</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Patient / Payor</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Date</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px] text-right">Settled Amount</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px] text-center">Status</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px] text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
          @forelse($logs ?? [] as $log)
            @php
              $method = $log->payment_method;
              $badge = match($method) {
                'GCASH' => 'bg-cyan-50 text-cyan-700 ring-cyan-700/10 dark:bg-cyan-950/60 dark:text-cyan-400 dark:ring-cyan-500/20',
                'MAYA' => 'bg-emerald-50 text-emerald-700 ring-emerald-700/10 dark:bg-emerald-950/60 dark:text-emerald-400 dark:ring-emerald-500/20',
                'CREDIT_CARD', 'DEBIT_CARD' => 'bg-blue-50 text-blue-700 ring-blue-700/10 dark:bg-blue-950/60 dark:text-blue-400 dark:ring-blue-500/20',
                default => 'bg-slate-100 text-slate-700 ring-slate-700/10 dark:bg-slate-800 dark:text-slate-300'
              };
            @endphp
            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
              <td class="py-3 px-4">
                <span class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset {{ $badge }}">
                  <i class="ph ph-credit-card text-[10px]"></i>
                  {{ $method }}
                </span>
              </td>
              <td class="py-3 px-4 font-mono font-medium text-slate-900 dark:text-white">
                {{ $log->transaction_channel_ref ?? $log->payment_reference }}
              </td>
              <td class="py-3 px-4 font-mono font-semibold text-emerald-600 dark:text-emerald-400">
                {{ $log->officialReceipt?->or_number ?? $log->payment_reference }}
              </td>
              <td class="py-3 px-4 font-medium text-slate-900 dark:text-white">
                {{ $log->officialReceipt?->payor_name ?: ($log->patientAccount?->full_name ?? 'Patient') }}
              </td>
              <td class="py-3 px-4 font-mono text-slate-600 dark:text-slate-300 whitespace-nowrap">
                {{ $log->payment_date ? $log->payment_date->format('M d, Y') : '-' }}
              </td>
              <td class="py-3 px-4 text-right font-mono tabular-nums font-semibold text-slate-900 dark:text-white">
                ₱{{ number_format((float) $log->amount, 2) }}
              </td>
              <td class="py-3 px-4 text-center">
                <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-700/10 dark:bg-emerald-950/60 dark:text-emerald-400">
                  <i class="ph-fill ph-check-circle text-[10px]"></i> SETTLED
                </span>
              </td>
              <td class="py-3 px-4 text-right">
                @if($log->journalEntry)
                  <a href="{{ route('gl.journal-entries') }}?search={{ $log->journalEntry->reference_number }}" class="inline-flex items-center gap-1 rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-mono font-semibold text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/60 dark:text-emerald-400 transition-colors" title="View Double-Entry Journal in General Ledger">
                    <i class="ph-fill ph-check-circle"></i>
                    <span>Posted: {{ $log->journalEntry->reference_number }}</span>
                  </a>
                @else
                  <form method="POST" action="{{ route('collection.payment-gateways.retrigger-gl', $log->id) }}" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1 rounded-lg border border-blue-200 bg-white px-2.5 py-1 text-xs font-semibold text-blue-700 shadow-sm hover:bg-blue-50 dark:border-blue-900/50 dark:bg-slate-900 dark:text-blue-400 dark:hover:bg-blue-950/30 transition-all" title="Post Missing Double-Entry Ledger Transaction">
                      <i class="ph ph-arrow-counter-clockwise"></i>
                      Re-Trigger GL
                    </button>
                  </form>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center py-8 text-slate-500 dark:text-slate-400">
                No digital gateway transaction records found.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if(method_exists($logs, 'links'))
      <div class="p-4 border-t border-slate-100 dark:border-slate-800/80">
        {{ $logs->links() }}
      </div>
    @endif
  </div>

</div>
@endsection

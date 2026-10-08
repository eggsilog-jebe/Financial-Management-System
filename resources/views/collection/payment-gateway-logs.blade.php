@extends('layouts.app')

@section('title', 'Payment Gateway Logs - Collection Management | FMS')
@section('module', 'collection')
@section('page', 'payment-gateways')

@section('content')
<div class="space-y-6">
  <!-- Executive Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
    <div>
      <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
          Payment Gateway &amp; E-Wallet Transaction Logs
        </h1>
    </div>

    <div class="flex items-center gap-2.5">
      <a 
        href="{{ route('collection.cashier-desk') }}" 
        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition-all dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
      >
        <i class="ph-bold ph-hand-coins text-teal-600"></i>
        <span>Cashier POS Desk</span>
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

  <!-- Metric Summary Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <x-stat-card 
      title="Digital Gateway Inflows" 
      :value="$totalOnline ?? 0" 
      icon="ph-globe-hemisphere-west" 
      color="emerald" 
      subtitle="E-Wallet &amp; Card settlements"
    />
    <x-stat-card 
      title="Digital Transactions" 
      :value="count($logs ?? [])" 
      :isCurrency="false"
      icon="ph-credit-card" 
      color="blue" 
      subtitle="Online gateway payments processed"
    />
    <x-stat-card 
      title="Settlement Status" 
      value="100% Synced" 
      :isCurrency="false"
      icon="ph-shield-check" 
      color="teal" 
      subtitle="Real-time gateway stream active"
    />
  </div>

  

  <!-- Data Table Card -->
  <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-800">
      <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
        <i class="ph-bold ph-device-mobile text-teal-600"></i> Digital Gateway &amp; POS Settlements Stream
      </h2>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
        <thead class="bg-slate-50/75 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:bg-slate-800/50 dark:border-slate-800 dark:text-slate-400">
          <tr>
            <th class="px-4 py-3.5">Channel / Provider</th>
            <th class="px-4 py-3.5">Channel Ref</th>
            <th class="px-4 py-3.5">Official Receipt #</th>
            <th class="px-4 py-3.5">Patient / Payor</th>
            <th class="px-4 py-3.5">Date</th>
            <th class="px-4 py-3.5 text-right">Settled Amount (₱)</th>
            <th class="px-4 py-3.5 text-center">Status</th>
            <th class="px-4 py-3.5 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
          @forelse($logs ?? [] as $log)
          @php
            $method = $log->payment_method;
          @endphp
          <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
            <td class="px-4 py-3">
              @if($method === 'GCASH')
                <x-status-badge status="blue" label="GCASH" size="sm" />
              @elseif($method === 'MAYA')
                <x-status-badge status="active" label="MAYA" size="sm" />
              @elseif(in_array($method, ['CREDIT_CARD', 'DEBIT_CARD']))
                <x-status-badge status="purple" label="{{ str_replace('_', ' ', $method) }}" size="sm" />
              @else
                <x-status-badge status="slate" label="{{ $method }}" size="sm" />
              @endif
            </td>
            <td class="px-4 py-3 font-mono font-semibold text-slate-900 dark:text-white">
              {{ $log->transaction_channel_ref ?? $log->payment_reference }}
            </td>
            <td class="px-4 py-3 font-mono font-bold text-teal-600 dark:text-teal-400">
              {{ $log->officialReceipt?->or_number ?? $log->payment_reference }}
            </td>
            <td class="px-4 py-3 text-slate-900 dark:text-white font-medium">
              {{ $log->officialReceipt?->payor_name ?: ($log->patientAccount?->full_name ?? 'Patient') }}
            </td>
            <td class="px-4 py-3 font-mono text-slate-600 dark:text-slate-300">
              {{ $log->payment_date ? $log->payment_date->format('M d, Y') : '-' }}
            </td>
            <td class="px-4 py-3 text-right font-mono font-bold tabular-nums text-emerald-600 dark:text-emerald-400">
              ₱{{ number_format((float) $log->amount, 2) }}
            </td>
            <td class="px-4 py-3 text-center">
              <x-status-badge status="active" label="SETTLED" size="sm" />
            </td>
            <td class="px-4 py-3 text-right">
              @if($log->journalEntry)
                <a 
                  href="{{ route('gl.journal-entries') }}?search={{ $log->journalEntry->reference_number }}" 
                  class="inline-flex items-center gap-1 rounded-lg bg-emerald-50 px-2.5 py-1 text-[11px] font-mono font-semibold text-emerald-700 ring-1 ring-emerald-600/20 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300" 
                  title="View Double-Entry Journal in General Ledger"
                >
                  <i class="ph-bold ph-check-circle"></i>
                  <span>Posted: {{ $log->journalEntry->reference_number }}</span>
                </a>
              @else
                <form method="POST" action="{{ route('collection.payment-gateways.retrigger-gl', $log->id) }}" class="inline">
                  @csrf
                  <button 
                    type="submit" 
                    class="inline-flex items-center gap-1 rounded-lg border border-teal-200 bg-white px-2.5 py-1 text-xs font-semibold text-teal-700 shadow-sm hover:bg-teal-50 dark:border-teal-800 dark:bg-slate-800 dark:text-teal-300 dark:hover:bg-slate-700 cursor-pointer" 
                    title="Post Missing Double-Entry Ledger Transaction"
                  >
                    <i class="ph-bold ph-arrows-clockwise text-teal-600"></i>
                    <span>Re-Trigger GL</span>
                  </button>
                </form>
              @endif
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="8" class="px-4 py-12 text-center text-slate-500 dark:text-slate-400">
              <i class="ph ph-globe text-3xl mb-2 text-slate-400"></i>
              <p>No digital gateway transaction records found.</p>
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <!-- Pagination Footer -->
    @if(method_exists($logs, 'links'))
      <div class="border-t border-slate-200 p-4 dark:border-slate-800">
        {{ $logs->links() }}
      </div>
    @endif
  </div>
</div>
@endsection

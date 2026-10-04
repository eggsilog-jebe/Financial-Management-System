@extends('layouts.app')

@section('title', 'EFT & Electronic Payouts - Disbursement | FMS')
@section('module', 'disbursement')
@section('page', 'eft-transfers')

@section('content')
<div class="space-y-6">
  <!-- Executive Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
    <div>
      <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
          Electronic Funds Transfers (EFT &amp; Bank Payouts)
        </h1>
    </div>

    <div class="flex items-center gap-2.5 flex-wrap">
      <a 
        href="{{ route('disbursement.eft-transfers.export') }}" 
        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition-all dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
      >
        <i class="ph-bold ph-download-simple text-teal-600"></i>
        <span>Export NACHA / Bank CSV</span>
      </a>
    </div>
  </div>

  <!-- Summary KPI Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
    <x-stat-card 
      title="Total EFT Payouts" 
      :value="$totalTransfers ?? 0" 
      :isCurrency="false"
      icon="ph-paper-plane-tilt" 
      color="slate" 
      subtitle="Cumulative electronic transfers"
    />
    <x-stat-card 
      title="PESONet Batch Volume" 
      :value="$pesonetAmount ?? 0" 
      icon="ph-bank" 
      color="blue" 
      subtitle="Same-day batch electronic payouts"
    />
    <x-stat-card 
      title="InstaPay Real-Time" 
      :value="$instapayAmount ?? 0" 
      icon="ph-lightning" 
      color="teal" 
      subtitle="Instant real-time transfers"
    />
    <x-stat-card 
      title="Total Electronic Disbursed" 
      :value="$totalAmount ?? 0" 
      icon="ph-check-circle" 
      color="emerald" 
      subtitle="Cleared electronic disbursements"
    />
  </div>

  <!-- Transfers Table Card -->
  
  <!-- Sub-Module Category Navigation Bar -->
  @include('partials.submodule-nav')

  <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 overflow-hidden dark:bg-slate-900 dark:ring-slate-800">
    <!-- Filter Toolbar -->
    <div class="border-b border-slate-200 p-4 dark:border-slate-800">
      <form method="GET" action="{{ route('disbursement.eft-transfers') }}" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 flex-wrap">
        <div class="flex items-center gap-2.5 flex-wrap">
          <div class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
            <i class="ph-bold ph-funnel"></i>
            <span>Channel:</span>
          </div>
          <select 
            name="channel" 
            class="rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-800 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-teal-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700" 
            onchange="this.form.submit()"
          >
            <option value="" {{ request('channel') === null || request('channel') === '' ? 'selected' : '' }}>All Electronic Channels</option>
            <option value="PESONET_EFT" {{ request('channel') === 'PESONET_EFT' ? 'selected' : '' }}>PESONet (Batch Same-Day)</option>
            <option value="INSTAPAY" {{ request('channel') === 'INSTAPAY' ? 'selected' : '' }}>InstaPay (Real-Time)</option>
            <option value="TELEGRAPHIC_TRANSFER" {{ request('channel') === 'TELEGRAPHIC_TRANSFER' ? 'selected' : '' }}>Telegraphic Transfer (TT / Wire)</option>
          </select>
        </div>

        <div class="relative w-full sm:w-72">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
            <i class="ph ph-magnifying-glass text-sm"></i>
          </div>
          <input 
            type="search" 
            name="search" 
            class="w-full rounded-xl border-0 bg-slate-50 py-1.5 pl-9 pr-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-teal-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700" 
            placeholder="Search voucher #, payee, ref..." 
            value="{{ request('search') }}"
          >
        </div>
      </form>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto custom-scrollbar">
      <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
        <thead class="bg-slate-50/75 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:bg-slate-800/50 dark:border-slate-800 dark:text-slate-400">
          <tr>
            <th class="px-4 py-3.5">Voucher Ref #</th>
            <th class="px-4 py-3.5">Beneficiary / Payee</th>
            <th class="px-4 py-3.5">Channel</th>
            <th class="px-4 py-3.5">Disbursing Bank</th>
            <th class="px-4 py-3.5">Date</th>
            <th class="px-4 py-3.5 text-right">Transfer Amount (₱)</th>
            <th class="px-4 py-3.5 text-center">Status</th>
            <th class="px-4 py-3.5">Release Reference</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
          @forelse($transfers as $t)
          @php
            $amt = (float) $t->net_disbursed_amount;
          @endphp
          <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
            <td class="px-4 py-3 font-mono font-bold text-teal-600 dark:text-teal-400">
              {{ $t->voucher_number }}
            </td>
            <td class="px-4 py-3">
              <div class="font-semibold text-slate-900 dark:text-white">{{ $t->payee_name }}</div>
              <div class="text-[11px] text-slate-500 dark:text-slate-400">{{ $t->description ?? 'Electronic Payout' }}</div>
            </td>
            <td class="px-4 py-3">
              <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[11px] font-mono font-medium bg-slate-100 text-slate-700 ring-1 ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700">
                {{ str_replace('_', ' ', $t->payment_method) }}
              </span>
            </td>
            <td class="px-4 py-3">
              <div class="font-medium text-slate-900 dark:text-white">{{ $t->bankAccount?->bank_name ?? 'Operating Bank' }}</div>
              <div class="text-[11px] text-slate-400 font-mono">{{ $t->bankAccount?->account_number ?? 'Acc' }}</div>
            </td>
            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
              {{ $t->voucher_date ? $t->voucher_date->format('M d, Y') : '—' }}
            </td>
            <td class="px-4 py-3 text-right font-mono font-bold tabular-nums text-slate-900 dark:text-white">
              ₱{{ number_format($amt, 2) }}
            </td>
            <td class="px-4 py-3 text-center">
              @if($t->status === 'RELEASED')
                <x-status-badge status="active" label="RELEASED" size="sm" />
              @elseif($t->status === 'APPROVED')
                <x-status-badge status="blue" label="APPROVED" size="sm" />
              @else
                <x-status-badge status="pending" label="{{ $t->status }}" size="sm" />
              @endif
            </td>
            <td class="px-4 py-3 font-mono text-[11px] text-slate-500 dark:text-slate-400">
              {{ $t->check_or_eft_ref ?? 'PENDING BATCH' }}
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="8" class="px-4 py-12 text-center text-slate-500 dark:text-slate-400">
              <i class="ph ph-paper-plane-tilt text-3xl mb-2 text-slate-400"></i>
              <p>No electronic transfers recorded for this filter.</p>
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <!-- Pagination & Meta -->
    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-slate-200 px-4 py-3 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
      <span>Showing {{ $transfers->firstItem() ?? 0 }} - {{ $transfers->lastItem() ?? 0 }} of {{ $transfers->total() }} Transfers</span>
      <div>
        {{ $transfers->links() }}
      </div>
    </div>
  </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Payment Receipts - Collection Management | FMS')
@section('module', 'collection')
@section('page', 'payment-receipts')

@section('content')
<div class="space-y-6" x-data="{
  init() {
    this.$watch('voidModalOpen', val => {
      document.body.classList.toggle('overflow-hidden', val);
      document.body.classList.toggle('modal-open', val);
    });
  },
  voidModalOpen: false,
  voidPaymentId: null,
  voidOrNumber: '',
  voidAmount: '',
  openVoid(id, orNo, amount) {
    this.voidPaymentId = id;
    this.voidOrNumber = orNo;
    this.voidAmount = amount;
    this.voidModalOpen = true;
  },
  closeVoid() {
    this.voidModalOpen = false;
    this.voidPaymentId = null;
  }
}" @keydown.escape.window="closeVoid()">

  {{-- Page Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <div class="flex items-center gap-2 text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">
        <a href="{{ route('accounting.dashboard') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">Overview</a>
        <i class="ph ph-caret-right text-[10px]"></i>
        <span class="text-slate-900 dark:text-slate-200 font-semibold">Payment Receipts</span>
      </div>
      <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
        Payment Receipts &amp; Official Receipts (OR) Hub
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
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    {{-- Total Collections --}}
    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
      <div class="flex items-center justify-between">
        <span class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Collections</span>
        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
          <i class="ph-duotone ph-receipt text-lg"></i>
        </div>
      </div>
      <div class="mt-2">
        <span class="text-2xl font-bold font-mono text-slate-900 dark:text-white">₱{{ number_format((float) ($totalCollected ?? 0), 2) }}</span>
      </div>
      <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Settled receipts total</p>
    </div>

    {{-- Cash Collections --}}
    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
      <div class="flex items-center justify-between">
        <span class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Cash Collections</span>
        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
          <i class="ph-duotone ph-money text-lg"></i>
        </div>
      </div>
      <div class="mt-2">
        <span class="text-2xl font-bold font-mono text-blue-600 dark:text-blue-400">₱{{ number_format((float) ($cashCollected ?? 0), 2) }}</span>
      </div>
      <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Cash drawer tenders</p>
    </div>

    {{-- Digital / E-Wallet --}}
    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
      <div class="flex items-center justify-between">
        <span class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Digital / E-Wallet</span>
        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-cyan-50 text-cyan-600 dark:bg-cyan-950/60 dark:text-cyan-400">
          <i class="ph-duotone ph-credit-card text-lg"></i>
        </div>
      </div>
      <div class="mt-2">
        <span class="text-2xl font-bold font-mono text-cyan-600 dark:text-cyan-400">₱{{ number_format((float) ($digitalCollected ?? 0), 2) }}</span>
      </div>
      <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Cards, GCash &amp; Maya</p>
    </div>

    {{-- Total Count --}}
    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
      <div class="flex items-center justify-between">
        <span class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Count</span>
        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400">
          <i class="ph-duotone ph-files text-lg"></i>
        </div>
      </div>
      <div class="mt-2">
        <span class="text-2xl font-bold font-mono text-slate-900 dark:text-white">{{ $payments->total() ?? count($payments) }}</span>
      </div>
      <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Audited receipt transactions</p>
    </div>
  </div>


{{-- Data Table Card --}}
  <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800 overflow-hidden">
    {{-- Filter Toolbar --}}
    <div class="border-b border-slate-200 p-4 dark:border-slate-800">
      <form method="GET" action="{{ route('collection.receipts') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-center">
        <div class="lg:col-span-4">
          <div class="relative">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
              <i class="ph ph-magnifying-glass"></i>
            </div>
            <input type="search" name="search" value="{{ request('search') }}" class="block w-full rounded-xl border-0 bg-slate-50 py-1.5 pl-9 pr-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700" placeholder="Search OR #, patient, payor...">
          </div>
        </div>
        <div class="lg:col-span-2">
          <select name="method" class="block w-full rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-800 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700">
            <option value="">All Methods</option>
            <option value="CASH" {{ request('method') === 'CASH' ? 'selected' : '' }}>CASH</option>
            <option value="GCASH" {{ request('method') === 'GCASH' ? 'selected' : '' }}>GCASH</option>
            <option value="MAYA" {{ request('method') === 'MAYA' ? 'selected' : '' }}>MAYA</option>
            <option value="CREDIT_CARD" {{ request('method') === 'CREDIT_CARD' ? 'selected' : '' }}>CREDIT CARD</option>
            <option value="DEBIT_CARD" {{ request('method') === 'DEBIT_CARD' ? 'selected' : '' }}>DEBIT CARD</option>
            <option value="CHECK" {{ request('method') === 'CHECK' ? 'selected' : '' }}>CHECK</option>
          </select>
        </div>
        <div class="lg:col-span-2">
          <input type="date" name="date_from" value="{{ request('date_from') }}" class="block w-full rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-800 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700" title="From Date">
        </div>
        <div class="lg:col-span-2">
          <input type="date" name="date_to" value="{{ request('date_to') }}" class="block w-full rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-800 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700" title="To Date">
        </div>
        <div class="lg:col-span-2 flex items-center gap-2">
          <button type="submit" class="inline-flex w-full items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 transition-all cursor-pointer">
            <i class="ph ph-funnel"></i>
            Filter
          </button>
          <a href="{{ route('collection.receipts') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white p-1.5 text-xs font-semibold text-slate-600 shadow-sm hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 transition-all" title="Reset filters">
            <i class="ph ph-arrow-counter-clockwise"></i>
          </a>
        </div>
      </form>
    </div>

    <div class="overflow-x-auto custom-scrollbar">
      <table class="w-full text-left border-collapse text-xs">
        <thead>
          <tr class="border-b border-slate-200/80 bg-slate-50/75 dark:border-slate-800 dark:bg-slate-950/50">
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Official Receipt #</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Date</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Patient / Payor</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Invoice Number</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Payment Method</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Shift / Terminal</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">General Ledger</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px] text-right">Amount Paid</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px] text-center">Status</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px] text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
          @forelse($payments as $pay)
            @php
              $orNo = $pay->officialReceipt?->or_number ?? $pay->payment_reference;
              $isCancelled = ($pay->officialReceipt?->status === 'CANCELLED');
            @endphp
            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors {{ $isCancelled ? 'opacity-60 bg-slate-50/50' : '' }}">
              <td class="py-3 px-4">
                <span class="font-mono font-semibold {{ $isCancelled ? 'line-through text-rose-500' : 'text-emerald-600 dark:text-emerald-400' }}">
                  {{ $orNo }}
                </span>
                <div class="text-[10px] text-slate-400 font-mono">{{ $pay->payment_reference }}</div>
              </td>
              <td class="py-3 px-4 font-mono text-slate-600 dark:text-slate-300 whitespace-nowrap">
                {{ $pay->payment_date ? $pay->payment_date->format('M d, Y') : '-' }}
              </td>
              <td class="py-3 px-4">
                <div class="font-semibold text-slate-900 dark:text-white">{{ $pay->officialReceipt?->payor_name ?: ($pay->patientAccount?->full_name ?? 'Walk-In') }}</div>
                <div class="text-[10px] text-slate-400 font-mono">{{ $pay->patientAccount?->patient_id_number }}</div>
              </td>
              <td class="py-3 px-4">
                <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-mono font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                  {{ $pay->invoice?->invoice_number ?? 'COP-SETTLED' }}
                </span>
              </td>
              <td class="py-3 px-4">
                <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                  {{ $pay->payment_method }}
                </span>
              </td>
              <td class="py-3 px-4 font-mono text-slate-500 dark:text-slate-400 text-[11px]">
                {{ $pay->cashierShift?->shift_code ?? '-' }}
              </td>
              <td class="py-3 px-4">
                <a href="{{ route('gl.journal-entries') }}?search={{ $pay->payment_reference }}" class="inline-flex items-center gap-1 rounded-md bg-blue-50 px-2 py-0.5 text-[11px] font-mono font-semibold text-blue-700 hover:bg-blue-100 dark:bg-blue-950/60 dark:text-blue-300 transition-colors">
                  <i class="ph ph-link-simple text-[10px]"></i> JE-COL-{{ $pay->payment_reference }}
                </a>
              </td>
              <td class="py-3 px-4 text-right font-mono tabular-nums font-semibold {{ $isCancelled ? 'text-slate-400 line-through' : 'text-slate-900 dark:text-white' }}">
                ₱{{ number_format((float) $pay->amount, 2) }}
              </td>
              <td class="py-3 px-4 text-center">
                @if($isCancelled)
                  <span class="inline-flex items-center gap-1 rounded-md bg-rose-50 px-2 py-0.5 text-[11px] font-semibold text-rose-700 ring-1 ring-inset ring-rose-700/10 dark:bg-rose-950/60 dark:text-rose-400">
                    <i class="ph ph-x-circle text-[10px]"></i> VOIDED
                  </span>
                @else
                  <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-700/10 dark:bg-emerald-950/60 dark:text-emerald-400">
                    <i class="ph ph-check-circle text-[10px]"></i> VALID
                  </span>
                @endif
              </td>
              <td class="py-3 px-4 text-right">
                <div class="inline-flex items-center gap-1.5">
                  <a href="{{ route('collection.receipts.print', $pay->id) }}" target="_blank" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white p-1.5 text-slate-600 shadow-sm hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 transition-all" title="Print Official Receipt">
                    <i class="ph ph-printer text-xs"></i>
                  </a>
                  @if(! $isCancelled)
                    <button @click="openVoid('{{ $pay->id }}', '{{ $orNo }}', '{{ number_format((float) $pay->amount, 2) }}')" class="inline-flex items-center justify-center rounded-lg border border-rose-200 bg-white p-1.5 text-rose-600 shadow-sm hover:bg-rose-50 dark:border-rose-900/50 dark:bg-slate-900 dark:text-rose-400 dark:hover:bg-rose-950/30 transition-all" type="button" title="Void Official Receipt">
                      <i class="ph ph-prohibit text-xs"></i>
                    </button>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="10" class="text-center py-8 text-slate-500 dark:text-slate-400">
                No official payment receipts found.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($payments->hasPages())
      <div class="p-4 border-t border-slate-100 dark:border-slate-800/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <span class="text-xs text-slate-500 dark:text-slate-400">Showing {{ $payments->count() }} of {{ $payments->total() }} Official Receipts</span>
        {{ $payments->links() }}
      </div>
    @endif
  </div>

  {{-- Modal: Void Official Receipt --}}
  <template x-teleport="body">
  <div x-show="voidModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div x-show="voidModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-950/60 backdrop-blur-md transition-opacity" @click="closeVoid()"></div>

    <div class="fixed inset-0 z-10 w-screen overflow-y-auto p-4 sm:p-6 md:p-20 flex min-h-full items-center justify-center pointer-events-none">
      <div x-show="voidModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" @click.outside="closeVoid()" class="pointer-events-auto relative mx-auto w-full max-w-lg transform rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800 transition-all text-left">

        <form method="POST" :action="'/collection-management/payment-receipts/' + voidPaymentId + '/void'">
          @csrf
          <div class="flex items-center gap-3 pb-4 border-b border-slate-100 dark:border-slate-800">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400">
              <i class="ph-duotone ph-warning text-xl"></i>
            </div>
            <div>
              <h3 class="text-sm font-bold text-slate-900 dark:text-white" id="modal-title">
                Void Official Receipt &amp; Reverse Ledger
              </h3>
              <p class="text-[11px] text-slate-500 dark:text-slate-400">Compliant with Philippine BIR CAS regulations</p>
            </div>
          </div>

          <div class="py-4 space-y-4">
            <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
              Voiding an official receipt marks it as <strong>CANCELLED</strong> in compliance with BIR CAS rules, restores the outstanding copay balance on the patient invoice, and automatically posts a balancing reversing General Ledger entry.
            </p>

            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3.5 space-y-2 dark:border-slate-800 dark:bg-slate-950/60">
              <div class="flex justify-between text-xs">
                <span class="text-slate-500 dark:text-slate-400">Official Receipt:</span>
                <span class="font-mono font-bold text-slate-900 dark:text-white" x-text="voidOrNumber"></span>
              </div>
              <div class="flex justify-between text-xs">
                <span class="text-slate-500 dark:text-slate-400">Amount to Reverse:</span>
                <span class="font-mono font-bold text-rose-600 dark:text-rose-400">₱<span x-text="voidAmount"></span></span>
              </div>
            </div>

            <div>
              <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                Reason for Voiding <span class="text-rose-500">*</span>
              </label>
              <select name="reason" class="block w-full rounded-xl border border-slate-200 bg-white py-2 px-3 text-xs text-slate-900 focus:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-500/20 dark:border-slate-800 dark:bg-slate-950 dark:text-white" required>
                <option value="Erroneous Amount Tendered">Erroneous Amount Tendered</option>
                <option value="Duplicate Official Receipt">Duplicate Official Receipt</option>
                <option value="Patient Transaction Cancelled / Reversed">Patient Transaction Cancelled / Reversed</option>
                <option value="Payment Method Input Correction">Payment Method Input Correction</option>
                <option value="Management Discretion / Refund">Management Discretion / Refund</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                Audit Justification Notes
              </label>
              <textarea name="notes" rows="2" class="block w-full rounded-xl border border-slate-200 bg-white py-2 px-3 text-xs text-slate-900 placeholder:text-slate-400 focus:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-500/20 dark:border-slate-800 dark:bg-slate-950 dark:text-white" placeholder="Additional audit details..."></textarea>
            </div>
          </div>

          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
            <button @click="closeVoid()" type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 transition-all">
              Cancel
            </button>
            <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-rose-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-rose-700 transition-all">
              <i class="ph ph-prohibit"></i>
              Confirm Receipt Void
            </button>
          </div>
        </form>

      </div>
    </div>
  </div>
  </template>

</div>
@endsection

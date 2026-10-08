@extends('layouts.app')

@section('title', 'Cashier Desk & POS Collection Counter | FMS')
@section('module', 'collection')
@section('page', 'cashier-desk')

@section('content')
<div class="space-y-6" x-data="{
  init() {
    this.$watch('openShiftModal', () => this.syncBodyLock());
    this.$watch('closeShiftModal', () => this.syncBodyLock());
  },
  syncBodyLock() {
    const anyOpen = this.openShiftModal || this.closeShiftModal;
    document.body.classList.toggle('overflow-hidden', anyOpen);
    document.body.classList.toggle('modal-open', anyOpen);
  },
  openShiftModal: false,
  closeShiftModal: false,
  modalActualCash: '',
  expectedCash: {{ $activeShift ? (float) $activeShift->expected_cash : 0 }},
  get variance() {
    const actual = parseFloat(this.modalActualCash) || 0;
    return (actual - this.expectedCash).toFixed(2);
  },
  get varianceFormatted() {
    const v = parseFloat(this.variance);
    const sign = v >= 0 ? '+' : '';
    return sign + '₱' + Math.abs(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }
}" @keydown.escape.window="openShiftModal = false; closeShiftModal = false">

  {{-- Page Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <div class="flex items-center gap-2 text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">
        <a href="{{ route('accounting.dashboard') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">Overview</a>
        <i class="ph ph-caret-right text-[10px]"></i>
        <a href="{{ route('collection.receipts') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">Collection</a>
        <i class="ph ph-caret-right text-[10px]"></i>
        <span class="text-slate-900 dark:text-slate-200 font-semibold">Cashier POS Desk</span>
      </div>
      <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
        Cashier Desk &amp; POS Collection Counter
      </h1>
    </div>
    <div class="flex flex-wrap items-center gap-2.5">
      <button class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 transition-all" type="button" onclick="location.reload()">
        <i class="ph ph-arrow-counter-clockwise"></i> Refresh
      </button>

      @if($activeShift)
        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-700/10 dark:bg-emerald-950/60 dark:text-emerald-400">
          <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
          Shift: <span class="font-mono">{{ $activeShift->shift_code }}</span> (OPEN)
        </span>
        <button @click="closeShiftModal = true" type="button" class="inline-flex items-center gap-1.5 rounded-xl bg-amber-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-amber-700 transition-all">
          <i class="ph ph-scales"></i> Close Shift &amp; Turnover
        </button>
      @else
        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-700/10 dark:bg-amber-950/60 dark:text-amber-400">
          <i class="ph-fill ph-warning"></i> No Active Shift
        </span>
        <button @click="openShiftModal = true" type="button" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 transition-all">
          <i class="ph ph-play-circle"></i> Open Terminal Shift
        </button>
      @endif
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

  {{-- Active Shift Drawer Metrics Summary Cards --}}
  @if($activeShift)
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
        <span class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider block mb-1">Assigned Station / Terminal</span>
        <h4 class="text-lg font-bold text-slate-900 dark:text-white truncate">{{ $activeShift->terminal_name }}</h4>
      </div>
      <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
        <span class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider block mb-1">Opening Cash Float</span>
        <h4 class="text-xl font-bold font-mono text-slate-700 dark:text-slate-300">₱{{ number_format((float) $activeShift->opening_cash_float, 2) }}</h4>
      </div>
      <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
        <span class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider block mb-1">Expected Cash in Drawer</span>
        <h4 class="text-xl font-bold font-mono text-emerald-600 dark:text-emerald-400">₱{{ number_format((float) $activeShift->expected_cash, 2) }}</h4>
      </div>
      <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
        <span class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider block mb-1">Digital Inflows (Card/QR)</span>
        <h4 class="text-xl font-bold font-mono text-blue-600 dark:text-blue-400">₱{{ number_format((float) $activeShift->total_digital_collections, 2) }}</h4>
      </div>
    </div>
  @endif


{{-- Main POS Counter Interface: 2-Column Responsive Layout --}}
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

    {{-- Left Column: Outstanding Patient Copays Queue --}}
    <div class="lg:col-span-7">
      <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800 overflow-hidden h-full flex flex-col justify-between">
        <div>
          <div class="p-5 border-b border-slate-100 dark:border-slate-800">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <i class="ph ph-receipt text-emerald-600 dark:text-emerald-400"></i>
                Outstanding Patient Copays
              </h3>
              <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-mono font-semibold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400">
                {{ $pendingInvoices->total() }} Records
              </span>
            </div>

            {{-- Filters & Search Bar --}}
            <form method="GET" action="{{ route('collection.cashier-desk') }}" class="space-y-3">
              <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                {{-- Admission Type Pill Filters --}}
                <div class="inline-flex rounded-xl bg-slate-100 p-1 dark:bg-slate-800 text-xs font-medium">
                  <a href="{{ route('collection.cashier-desk', array_merge(request()->query(), ['admission_type' => ''])) }}" 
                     class="rounded-lg px-3 py-1 transition-all {{ empty($admissionType) ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-900 dark:text-white font-semibold' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400' }}">
                    All
                  </a>
                  <a href="{{ route('collection.cashier-desk', array_merge(request()->query(), ['admission_type' => 'Inpatient'])) }}" 
                     class="rounded-lg px-3 py-1 transition-all {{ $admissionType === 'Inpatient' ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-900 dark:text-white font-semibold' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400' }}">
                    Inpatient
                  </a>
                  <a href="{{ route('collection.cashier-desk', array_merge(request()->query(), ['admission_type' => 'Outpatient'])) }}" 
                     class="rounded-lg px-3 py-1 transition-all {{ $admissionType === 'Outpatient' ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-900 dark:text-white font-semibold' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400' }}">
                    Outpatient
                  </a>
                </div>

                {{-- Hide Zero-Balance Bills Toggle --}}
                <label class="flex items-center gap-2 cursor-pointer text-xs font-medium text-slate-600 dark:text-slate-400">
                  <input type="checkbox" id="hideZeroToggle" name="hide_zero" value="1" 
                         {{ ($hideZero ?? true) ? 'checked' : '' }} onchange="this.form.submit()"
                         class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                  <span>Hide Zero-Balance Bills</span>
                </label>
              </div>

              {{-- Search Input --}}
              <div class="flex gap-2">
                <div class="relative flex-1">
                  <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <i class="ph ph-magnifying-glass"></i>
                  </div>
                  <input type="text" name="q" value="{{ $search }}" class="block w-full rounded-xl border border-slate-200 bg-white py-2 pl-9 pr-3 text-xs text-slate-900 placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-800 dark:bg-slate-950 dark:text-white" placeholder="Search by Patient Name, MRN #, or Invoice #...">
                </div>
                <button type="submit" class="inline-flex items-center gap-1 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 transition-all">
                  Search
                </button>
                @if($search || $admissionType)
                  <a href="{{ route('collection.cashier-desk') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white p-2 text-xs font-semibold text-slate-600 shadow-sm hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 transition-all" title="Reset filters">
                    <i class="ph ph-arrow-counter-clockwise"></i>
                  </a>
                @endif
              </div>
            </form>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="border-b border-slate-200/80 bg-slate-50/75 dark:border-slate-800 dark:bg-slate-950/50">
                  <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Invoice #</th>
                  <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Patient Details</th>
                  <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Admission</th>
                  <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px] text-right">Patient Copay</th>
                  <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px] text-center">Action</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                @forelse($pendingInvoices as $inv)
                  @php
                    $copayFormatted = number_format((float) $inv->patient_payable, 2, '.', '');
                    $copayVal = (float) $copayFormatted;
                    $isZeroCopay = ($copayVal <= 0.00);
                  @endphp
                  <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors {{ $isZeroCopay ? 'opacity-60 bg-slate-50/40' : '' }}" x-data="{ payModalOpen: false, init() { this.$watch('payModalOpen', val => { document.body.classList.toggle('overflow-hidden', val); document.body.classList.toggle('modal-open', val); }); } }">
                    <td class="py-3.5 px-4">
                      <span class="font-mono font-semibold text-slate-900 dark:text-white">{{ $inv->invoice_number }}</span>
                      <span class="block text-[10px] text-slate-400">{{ $inv->invoice_date ? $inv->invoice_date->format('M d, Y') : '-' }}</span>
                    </td>
                    <td class="py-3.5 px-4">
                      <div class="font-semibold text-slate-900 dark:text-white {{ $isZeroCopay ? 'text-slate-400' : '' }}">{{ $inv->patientAccount?->full_name ?? 'Patient' }}</div>
                      <span class="font-mono text-[10px] text-slate-400">{{ $inv->patientAccount?->patient_id_number ?? 'MRN-N/A' }}</span>
                    </td>
                    <td class="py-3.5 px-4">
                      <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                        {{ $inv->patientAccount?->admission_type ?? 'Outpatient' }}
                      </span>
                    </td>
                    <td class="py-3.5 px-4 text-right font-mono tabular-nums font-semibold {{ $isZeroCopay ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-900 dark:text-white' }}">
                      ₱{{ number_format($copayVal, 2) }}
                    </td>
                    <td class="py-3.5 px-4 text-center">
                      @if($isZeroCopay)
                        <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400">
                          <i class="ph-fill ph-check-circle"></i> Cleared / ₱0.00
                        </span>
                      @else
                        <button type="button" @click="payModalOpen = true" class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-3 py-1 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 transition-all">
                          <i class="ph ph-coins"></i> Settle
                        </button>

                        {{-- Settle Payment Alpine Modal --}}
                        <template x-teleport="body">
                        <div x-show="payModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto text-left" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                          <div x-show="payModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-950/60 backdrop-blur-md transition-opacity" @click="payModalOpen = false"></div>

                          <div class="fixed inset-0 z-10 w-screen overflow-y-auto p-4 sm:p-6 md:p-20 flex min-h-full items-center justify-center pointer-events-none">
                            <div x-show="payModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" @click.outside="payModalOpen = false" class="pointer-events-auto relative mx-auto w-full max-w-lg transform rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800 transition-all text-left"
                                 x-data="{
                                    settlementAmount: '{{ $copayFormatted }}',
                                    paymentMethod: 'CASH',
                                    amountTendered: '{{ $copayFormatted }}',
                                    splitCashAmount: '0.00',
                                    splitCashTendered: '0.00',
                                    splitDigitalAmount: '0.00',
                                    splitDigitalChannel: 'CREDIT_CARD',
                                    get changeAmount() {
                                        if (this.paymentMethod === 'SPLIT_PAYMENT') {
                                            const c = parseFloat(this.splitCashAmount) || 0;
                                            const t = parseFloat(this.splitCashTendered) || 0;
                                            return (t >= c ? (t - c) : 0).toFixed(2);
                                        }
                                        if (this.paymentMethod !== 'CASH') return '0.00';
                                        const s = parseFloat(this.settlementAmount) || 0;
                                        const t = parseFloat(this.amountTendered) || 0;
                                        return (t >= s ? (t - s) : 0).toFixed(2);
                                    },
                                    get splitTotalSum() {
                                        const c = parseFloat(this.splitCashAmount) || 0;
                                        const d = parseFloat(this.splitDigitalAmount) || 0;
                                        return c + d;
                                    },
                                    get isSplitValid() {
                                        if (this.paymentMethod !== 'SPLIT_PAYMENT') return true;
                                        const s = parseFloat(this.settlementAmount) || 0;
                                        const sum = this.splitTotalSum;
                                        return Math.abs(sum - s) < 0.01 && sum > 0;
                                    },
                                    get isUnderTendered() {
                                        if (this.paymentMethod === 'SPLIT_PAYMENT') {
                                            return ! this.isSplitValid;
                                        }
                                        if (this.paymentMethod !== 'CASH') return false;
                                        const s = parseFloat(this.settlementAmount) || 0;
                                        const t = parseFloat(this.amountTendered) || 0;
                                        return t < s;
                                    },
                                    get formattedChange() {
                                        const c = parseFloat(this.changeAmount) || 0;
                                        return '₱' + c.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                    },
                                    onChannelChange() {
                                        if (this.paymentMethod === 'SPLIT_PAYMENT') {
                                            const s = parseFloat(this.settlementAmount) || 0;
                                            const half = (s / 2).toFixed(2);
                                            this.splitCashAmount = half;
                                            this.splitDigitalAmount = (s - parseFloat(half)).toFixed(2);
                                            this.splitCashTendered = half;
                                        } else if (this.paymentMethod !== 'CASH') {
                                            this.amountTendered = this.settlementAmount;
                                        }
                                    }
                                 }">
                              <form method="POST" action="{{ route('collection.cashier-desk.collect') }}">
                                @csrf
                                <input type="hidden" name="invoice_id" value="{{ $inv->id }}">
                                @if($activeShift)
                                  <input type="hidden" name="cashier_shift_id" value="{{ $activeShift->id }}">
                                @endif

                                <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                                  <div class="flex items-center gap-2.5">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                                      <i class="ph-duotone ph-receipt text-xl"></i>
                                    </div>
                                    <div>
                                      <h3 class="text-sm font-bold text-slate-900 dark:text-white">Counter Settlement</h3>
                                      <p class="text-[11px] text-slate-400">Official receipt issue &amp; copay posting</p>
                                    </div>
                                  </div>
                                  <button @click="payModalOpen = false" type="button" class="rounded-lg p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                                    <i class="ph ph-x text-base"></i>
                                  </button>
                                </div>

                                <div class="py-4 space-y-4">
                                  {{-- Patient Summary Card --}}
                                  <div class="rounded-xl border border-slate-100 bg-slate-50 p-3.5 space-y-2 dark:border-slate-800 dark:bg-slate-950/60">
                                    <div class="flex justify-between text-xs">
                                      <span class="text-slate-500 dark:text-slate-400">Patient:</span>
                                      <span class="font-bold text-slate-900 dark:text-white">{{ $inv->patientAccount?->full_name ?? 'Patient' }}</span>
                                    </div>
                                    <div class="flex justify-between text-xs">
                                      <span class="text-slate-500 dark:text-slate-400">Invoice Reference:</span>
                                      <span class="font-mono font-medium text-slate-700 dark:text-slate-300">{{ $inv->invoice_number }}</span>
                                    </div>
                                    <div class="flex justify-between items-baseline pt-2 border-t border-slate-200/60 dark:border-slate-800">
                                      <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Net Copay Due:</span>
                                      <span class="text-lg font-mono font-bold text-slate-900 dark:text-white">₱{{ number_format($copayVal, 2) }}</span>
                                    </div>
                                  </div>

                                  <div>
                                    <div class="flex justify-between items-center mb-1.5">
                                      <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                        Settlement Amount (₱) <span class="text-rose-500">*</span>
                                      </label>
                                      <span class="text-[10px] text-slate-400">Partial payment allowed</span>
                                    </div>
                                    <input type="number" step="0.01" min="0.01" max="{{ $copayFormatted }}" name="amount" id="payAmount{{ $inv->id }}" class="block w-full rounded-xl border border-slate-200 bg-white py-2 px-3 text-xs font-mono font-bold text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-800 dark:bg-slate-950 dark:text-white" x-model="settlementAmount" required>
                                  </div>

                                  <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                      Payment Channel <span class="text-rose-500">*</span>
                                    </label>
                                    <select name="payment_method" id="payMethod{{ $inv->id }}" class="block w-full rounded-xl border border-slate-200 bg-white py-2 px-3 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-800 dark:bg-slate-950 dark:text-white font-medium" required x-model="paymentMethod" @change="onChannelChange()">
                                      <option value="CASH">Cash (Cash Drawer)</option>
                                      <option value="GCASH">GCash E-Wallet</option>
                                      <option value="MAYA">Maya Digital Wallet</option>
                                      <option value="QR_PH">QR Ph Interoperable</option>
                                      <option value="CREDIT_CARD">Credit Card (POS Terminal)</option>
                                      <option value="DEBIT_CARD">Debit Card (POS Terminal)</option>
                                      <option value="BANK_TRANSFER">Bank Transfer / EFT</option>
                                      <option value="CHECK">Bank Manager's Check</option>
                                      <option value="SPLIT_PAYMENT">Split / Multiple Tender (Cash + Card/Digital)</option>
                                    </select>
                                  </div>

                                  {{-- Single Cash Channel Row --}}
                                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" x-show="paymentMethod === 'CASH'">
                                    <div>
                                      <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                        Amount Tendered (₱) <span class="text-rose-500">*</span>
                                      </label>
                                      <input type="number" step="0.01" min="0" name="tendered_amount" id="tendered{{ $inv->id }}" class="block w-full rounded-xl border border-slate-200 bg-white py-2 px-3 text-xs font-mono text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-800 dark:bg-slate-950 dark:text-white" placeholder="0.00" x-model="amountTendered">
                                    </div>
                                    <div>
                                      <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                        Change (₱)
                                      </label>
                                      <input type="text" id="change{{ $inv->id }}" class="block w-full rounded-xl border border-slate-200 bg-slate-100 py-2 px-3 text-xs font-mono font-bold text-emerald-600 dark:border-slate-800 dark:bg-slate-800 dark:text-emerald-400" :value="formattedChange" readonly>
                                    </div>
                                  </div>

                                  {{-- Multi-Tender / Split Breakdown --}}
                                  <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 space-y-3 dark:border-slate-800 dark:bg-slate-950/60" x-show="paymentMethod === 'SPLIT_PAYMENT'">
                                    <div class="text-xs font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                                      <i class="ph ph-arrows-split"></i> Multi-Tender Breakdown
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 text-xs">
                                      <div class="sm:col-span-5">
                                        <label class="text-[10px] text-slate-500 block mb-1">Tender 1 (Cash)</label>
                                        <input type="text" class="block w-full rounded-lg border border-slate-200 bg-white py-1.5 px-2 text-xs" value="Cash Drawer" readonly>
                                      </div>
                                      <div class="sm:col-span-3">
                                        <label class="text-[10px] text-slate-500 block mb-1">Amount (₱)</label>
                                        <input type="number" step="0.01" min="0" name="split_cash_amount" class="block w-full rounded-lg border border-slate-200 bg-white py-1.5 px-2 text-xs font-mono" placeholder="0.00" x-model="splitCashAmount">
                                      </div>
                                      <div class="sm:col-span-4">
                                        <label class="text-[10px] text-slate-500 block mb-1">Tendered (₱)</label>
                                        <input type="number" step="0.01" min="0" class="block w-full rounded-lg border border-slate-200 bg-white py-1.5 px-2 text-xs font-mono" placeholder="0.00" x-model="splitCashTendered">
                                      </div>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 text-xs">
                                      <div class="sm:col-span-5">
                                        <label class="text-[10px] text-slate-500 block mb-1">Tender 2 (Digital/Card)</label>
                                        <select name="split_digital_channel" class="block w-full rounded-lg border border-slate-200 bg-white py-1.5 px-2 text-xs font-medium" x-model="splitDigitalChannel">
                                          <option value="CREDIT_CARD">Credit Card POS</option>
                                          <option value="DEBIT_CARD">Debit Card POS</option>
                                          <option value="GCASH">GCash E-Wallet</option>
                                          <option value="MAYA">Maya Digital Wallet</option>
                                          <option value="BANK_TRANSFER">Bank Transfer / EFT</option>
                                        </select>
                                      </div>
                                      <div class="sm:col-span-3">
                                        <label class="text-[10px] text-slate-500 block mb-1">Amount (₱)</label>
                                        <input type="number" step="0.01" min="0" name="split_digital_amount" class="block w-full rounded-lg border border-slate-200 bg-white py-1.5 px-2 text-xs font-mono" placeholder="0.00" x-model="splitDigitalAmount">
                                      </div>
                                      <div class="sm:col-span-4">
                                        <label class="text-[10px] text-slate-500 block mb-1">Auth / Ref #</label>
                                        <input type="text" name="split_digital_ref" class="block w-full rounded-lg border border-slate-200 bg-white py-1.5 px-2 text-xs font-mono" placeholder="Ref #">
                                      </div>
                                    </div>
                                  </div>

                                  <div x-show="paymentMethod !== 'CASH' && paymentMethod !== 'SPLIT_PAYMENT'">
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                      Transaction / Auth Code
                                    </label>
                                    <input type="text" name="gateway_transaction_id" class="block w-full rounded-xl border border-slate-200 bg-white py-2 px-3 text-xs font-mono text-slate-900 placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-800 dark:bg-slate-950 dark:text-white" placeholder="e.g. GCash Ref # or POS Auth Code">
                                  </div>

                                  <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                      Payor Name (for BIR Receipt)
                                    </label>
                                    <input type="text" name="payor_name" class="block w-full rounded-xl border border-slate-200 bg-white py-2 px-3 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-800 dark:bg-slate-950 dark:text-white" value="{{ $inv->patientAccount?->full_name }}" placeholder="e.g. Payor Full Name">
                                  </div>

                                  <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                      Receipt Remarks
                                    </label>
                                    <input type="text" name="notes" class="block w-full rounded-xl border border-slate-200 bg-white py-2 px-3 text-xs text-slate-900 placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-800 dark:bg-slate-950 dark:text-white" placeholder="Settlement memo...">
                                  </div>
                                </div>

                                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                                  <button @click="payModalOpen = false" type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 transition-all">
                                    Cancel
                                  </button>
                                  <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 transition-all" :disabled="isUnderTendered">
                                    <i class="ph ph-check-circle"></i>
                                    Post &amp; Issue BIR Receipt
                                  </button>
                                </div>
                              </form>
                            </div>
                          </div>
                        </div>
                        </template>
                      @endif
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5" class="text-center py-8 text-slate-500 dark:text-slate-400">
                      No outstanding patient copays found.
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

        <div class="p-4 border-t border-slate-100 dark:border-slate-800/80">
          {{ $pendingInvoices->links() }}
        </div>
      </div>
    </div>

    {{-- Right Column: Recent Official Receipts --}}
    <div class="lg:col-span-5">
      <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800 overflow-hidden h-full">
        <div class="p-5 border-b border-slate-100 dark:border-slate-800">
          <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <i class="ph ph-check-circle text-emerald-600 dark:text-emerald-400"></i>
            Issued Official Receipts (OR)
          </h3>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Real-time BIR EOPT Official Receipt series register.</p>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="border-b border-slate-200/80 bg-slate-50/75 dark:border-slate-800 dark:bg-slate-950/50">
                <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">OR Number</th>
                <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Patient / Payor</th>
                <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Channel</th>
                <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px] text-right">Amount</th>
                <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px] text-center">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
              @forelse($todayPayments as $pay)
                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                  <td class="py-3 px-4">
                    <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-mono font-semibold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400">
                      {{ $pay->officialReceipt?->or_number ?? 'OR-PENDING' }}
                    </span>
                    <span class="block text-[10px] text-slate-400 font-mono mt-0.5">{{ $pay->payment_date ? $pay->payment_date->format('M d, Y') : '-' }}</span>
                  </td>
                  <td class="py-3 px-4">
                    <div class="font-semibold text-slate-900 dark:text-white">{{ $pay->officialReceipt?->payor_name ?: ($pay->patientAccount?->full_name ?? 'Patient') }}</div>
                    <span class="text-[10px] text-slate-400 font-mono">{{ $pay->payment_reference }}</span>
                  </td>
                  <td class="py-3 px-4">
                    <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                      {{ $pay->payment_method }}
                    </span>
                  </td>
                  <td class="py-3 px-4 text-right font-mono tabular-nums font-semibold text-emerald-600 dark:text-emerald-400">
                    ₱{{ number_format((float) $pay->amount, 2) }}
                  </td>
                  <td class="py-3 px-4 text-center">
                    <a href="{{ route('collection.receipts.print', $pay->id) }}" target="_blank" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white p-1 text-slate-600 shadow-sm hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 transition-all" title="Print BIR EOPT Official Receipt">
                      <i class="ph ph-printer text-xs"></i>
                    </a>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center py-8 text-slate-500 dark:text-slate-400">
                    No payments recorded today.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>

  {{-- Bottom Section: All Terminal Shifts Supervision --}}
  <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800 overflow-hidden">
    <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
      <div>
        <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
          <i class="ph ph-desktop text-emerald-600 dark:text-emerald-400"></i>
          All Hospital POS Stations &amp; Terminal Shifts
        </h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Shift monitoring, cash drawers, and supervisor reconciliation.</p>
      </div>
      <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-mono font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
        {{ count($shifts ?? []) }} Stations
      </span>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse text-xs">
        <thead>
          <tr class="border-b border-slate-200/80 bg-slate-50/75 dark:border-slate-800 dark:bg-slate-950/50">
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Shift Code</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Station / Terminal</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Cashier Officer</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px] text-right">Opening Float</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px] text-right">Drawer Expected / Counted</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Shift Start</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px] text-center">Status</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px] text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
          @forelse($shifts ?? [] as $t)
            @php
              $tId = $t->shift_code;
              $loc = $t->terminal_name;
              $cashier = $t->cashier?->name ?? 'Cashier';
              $float = '₱' . number_format((float) $t->opening_cash_float, 2);
              $cash = ($t->status === 'OPEN') 
                  ? '₱' . number_format((float) $t->expected_cash, 2)
                  : '₱' . number_format((float) $t->actual_cash_counted, 2);
              $start = $t->opened_at ? $t->opened_at->format('M d, h:i A') : '-';
              $status = $t->status;
              $badge = match($status) {
                  'OPEN' => 'bg-emerald-50 text-emerald-700 ring-emerald-700/10 dark:bg-emerald-950/60 dark:text-emerald-400',
                  'CLOSED' => 'bg-slate-100 text-slate-700 ring-slate-700/10 dark:bg-slate-800 dark:text-slate-300',
                  'RECONCILED' => 'bg-blue-50 text-blue-700 ring-blue-700/10 dark:bg-blue-950/60 dark:text-blue-400',
                  default => 'bg-slate-100 text-slate-700 dark:bg-slate-800'
              };
            @endphp
            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
              <td class="py-3.5 px-4 font-mono font-semibold text-emerald-600 dark:text-emerald-400">{{ $tId }}</td>
              <td class="py-3.5 px-4">
                <div class="font-semibold text-slate-900 dark:text-white">{{ $loc }}</div>
                <span class="text-[10px] text-slate-400">Hospital POS Counter</span>
              </td>
              <td class="py-3.5 px-4 text-slate-800 dark:text-slate-200 font-medium">{{ $cashier }}</td>
              <td class="py-3.5 px-4 text-right font-mono text-slate-500 dark:text-slate-400">{{ $float }}</td>
              <td class="py-3.5 px-4 text-right font-mono tabular-nums font-semibold text-emerald-600 dark:text-emerald-400">{{ $cash }}</td>
              <td class="py-3.5 px-4 font-mono text-slate-600 dark:text-slate-300 whitespace-nowrap">{{ $start }}</td>
              <td class="py-3.5 px-4 text-center">
                <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset {{ $badge }}">
                  {{ $status }}
                </span>
              </td>
              <td class="py-3.5 px-4 text-right">
                @if($status === 'CLOSED')
                  <form method="POST" action="{{ route('collection.shifts.reconcile', $t->id) }}" class="inline">
                    @csrf
                    <button class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 transition-all cursor-pointer" type="submit" title="Reconcile as Supervisor">
                      <i class="ph ph-shield-check"></i> Reconcile
                    </button>
                  </form>
                @elseif($status === 'RECONCILED')
                  <span class="inline-flex items-center gap-1 text-[11px] text-slate-400 font-medium">
                    <i class="ph ph-lock"></i> Reconciled
                  </span>
                @else
                  <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400">
                    <i class="ph ph-activity"></i> Active
                  </span>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center py-8 text-slate-500 dark:text-slate-400">
                No cashier shift records available.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- Modal: Open Terminal Shift --}}
  <template x-teleport="body">
  <div x-show="openShiftModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div x-show="openShiftModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-950/60 backdrop-blur-md transition-opacity" @click="openShiftModal = false"></div>

    <div class="fixed inset-0 z-10 w-screen overflow-y-auto p-4 sm:p-6 md:p-20 flex min-h-full items-center justify-center pointer-events-none">
      <div x-show="openShiftModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" @click.outside="openShiftModal = false" class="pointer-events-auto relative mx-auto w-full max-w-md transform rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800 transition-all text-left">

        <form method="POST" action="{{ route('collection.shifts.open') }}">
          @csrf
          <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
            <div class="flex items-center gap-2.5">
              <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                <i class="ph-duotone ph-play-circle text-xl"></i>
              </div>
              <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white" id="modal-title">
                  Open Cashier Terminal Shift
                </h3>
                <p class="text-[11px] text-slate-400">Assign physical petty float drawer</p>
              </div>
            </div>
            <button @click="openShiftModal = false" type="button" class="rounded-lg p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
              <i class="ph ph-x text-base"></i>
            </button>
          </div>

          <div class="py-4 space-y-4">
            <div>
              <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                Select POS Station <span class="text-rose-500">*</span>
              </label>
              <select name="terminal_name" class="block w-full rounded-xl border border-slate-200 bg-white py-2 px-3 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-800 dark:bg-slate-950 dark:text-white" required>
                <option value="POS-MAIN-01 (Main Lobby)">POS-MAIN-01 (Main Lobby)</option>
                <option value="POS-ER-01 (Emergency Room)">POS-ER-01 (Emergency Room)</option>
                <option value="POS-PHARM-01 (Pharmacy Central)">POS-PHARM-01 (Pharmacy Central)</option>
                <option value="POS-OPD-01 (Outpatient Consultation)">POS-OPD-01 (Outpatient Consultation)</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                Opening Cash Float (₱) <span class="text-rose-500">*</span>
              </label>
              <input type="number" step="0.01" min="0" name="opening_cash_float" class="block w-full rounded-xl border border-slate-200 bg-white py-2 px-3 text-xs font-mono text-right text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-800 dark:bg-slate-950 dark:text-white" value="5000.00" required>
              <span class="text-[10px] text-slate-400 mt-1 block">Physical currency placed in cashier drawer at beginning of shift.</span>
            </div>
          </div>

          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
            <button @click="openShiftModal = false" type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 transition-all">
              Cancel
            </button>
            <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 transition-all">
              <i class="ph ph-check"></i>
              Start Shift Now
            </button>
          </div>
        </form>

      </div>
    </div>
  </div>
  </template>

  {{-- Modal: Close Shift & Turnover --}}
  @if($activeShift)
    <template x-teleport="body">
    <div x-show="closeShiftModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
      <div x-show="closeShiftModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-950/60 backdrop-blur-md transition-opacity" @click="closeShiftModal = false"></div>

      <div class="fixed inset-0 z-10 w-screen overflow-y-auto p-4 sm:p-6 md:p-20 flex min-h-full items-center justify-center pointer-events-none">
        <div x-show="closeShiftModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" @click.outside="closeShiftModal = false" class="pointer-events-auto relative mx-auto w-full max-w-lg transform rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800 transition-all text-left">

          <form method="POST" action="{{ route('collection.shifts.close') }}">
            @csrf
            <input type="hidden" name="shift_id" value="{{ $activeShift->id }}">

            <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
              <div class="flex items-center gap-2.5">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400">
                  <i class="ph-duotone ph-scales text-xl"></i>
                </div>
                <div>
                  <h3 class="text-sm font-bold text-slate-900 dark:text-white" id="modal-title">
                    Close Shift &amp; Drawer Turnover
                  </h3>
                  <p class="text-[11px] text-slate-400">Physical cash count reconciliation</p>
                </div>
              </div>
              <button @click="closeShiftModal = false" type="button" class="rounded-lg p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                <i class="ph ph-x text-base"></i>
              </button>
            </div>

            <div class="py-4 space-y-4">
              {{-- Shift Metrics Breakdown --}}
              <div class="rounded-xl border border-slate-100 bg-slate-50 p-3.5 space-y-2 dark:border-slate-800 dark:bg-slate-950/60 text-xs">
                <div class="flex justify-between">
                  <span class="text-slate-500 dark:text-slate-400">Terminal Station:</span>
                  <span class="font-bold text-slate-900 dark:text-white">{{ $activeShift->terminal_name }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-slate-500 dark:text-slate-400">Shift Code:</span>
                  <span class="font-mono font-semibold text-emerald-600 dark:text-emerald-400">{{ $activeShift->shift_code }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-slate-500 dark:text-slate-400">Opening Cash Float:</span>
                  <span class="font-mono text-slate-700 dark:text-slate-300">₱{{ number_format((float) $activeShift->opening_cash_float, 2) }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-slate-500 dark:text-slate-400">Digital / Card Collections:</span>
                  <span class="font-mono text-slate-700 dark:text-slate-300">₱{{ number_format((float) $activeShift->total_digital_collections, 2) }}</span>
                </div>
                <div class="flex justify-between pt-2 border-t border-slate-200/60 dark:border-slate-800 font-bold">
                  <span class="text-slate-700 dark:text-slate-300">System Expected Cash:</span>
                  <span class="font-mono text-emerald-600 dark:text-emerald-400">₱{{ number_format((float) $activeShift->expected_cash, 2) }}</span>
                </div>
              </div>

              <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                  Actual Physical Cash Counted (₱) <span class="text-rose-500">*</span>
                </label>
                <input type="number" step="0.01" min="0" name="actual_cash_counted" x-model="modalActualCash" class="block w-full rounded-xl border border-slate-200 bg-white py-2 px-3 text-xs font-mono font-bold text-right text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-800 dark:bg-slate-950 dark:text-white" required>
              </div>

              <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                  Calculated Drawer Variance
                </label>
                <input type="text" class="block w-full rounded-xl border border-slate-200 bg-slate-100 py-2 px-3 text-xs font-mono font-bold text-right dark:border-slate-800 dark:bg-slate-800" :class="parseFloat(variance) === 0 ? 'text-emerald-600 dark:text-emerald-400' : (parseFloat(variance) < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-blue-600 dark:text-blue-400')" :value="varianceFormatted" readonly>
              </div>

              <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                  Variance Reason / Explanation (Optional)
                </label>
                <textarea name="variance_reason" rows="2" class="block w-full rounded-xl border border-slate-200 bg-white py-2 px-3 text-xs text-slate-900 placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-800 dark:bg-slate-950 dark:text-white" placeholder="Explain any cash overage or shortage..."></textarea>
              </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
              <button @click="closeShiftModal = false" type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 transition-all">
                Cancel
              </button>
              <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-amber-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-amber-700 transition-all">
                <i class="ph ph-lock"></i>
                Close &amp; Generate Turnover
              </button>
            </div>
          </form>

        </div>
      </div>
    </div>
    </template>
  @endif

  {{-- Printable Shift Turnover / Bag Tag Summary Modal --}}
  @if(session('turnover_summary'))
    @php $t = session('turnover_summary'); @endphp
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-md">
      <div class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800 text-left">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
          <div class="flex items-center gap-2">
            <i class="ph ph-tag text-emerald-600 dark:text-emerald-400 text-xl"></i>
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Physical Cash Turnover Bag Tag</h3>
          </div>
          <a href="{{ route('collection.cashier-desk') }}" class="rounded-lg p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
            <i class="ph ph-x text-base"></i>
          </a>
        </div>

        <div class="py-4 space-y-4" id="printableTurnoverArea">
          <div class="text-center pb-3 border-b border-slate-100 dark:border-slate-800">
            <h4 class="font-bold text-slate-900 dark:text-white text-xs uppercase tracking-wider">St. Jude Metropolitan Medical Center</h4>
            <p class="text-[10px] text-slate-400">Cashier Shift Custody Turnover Slip &bull; BIR CAS Audited</p>
          </div>

          <div class="space-y-1 text-xs">
            <div class="flex justify-between"><span class="text-slate-500">Shift Code:</span><strong class="font-mono text-slate-900 dark:text-white">{{ $t['shift_code'] }}</strong></div>
            <div class="flex justify-between"><span class="text-slate-500">Terminal:</span><strong class="text-slate-900 dark:text-white">{{ $t['terminal_name'] }}</strong></div>
            <div class="flex justify-between"><span class="text-slate-500">Cashier Officer:</span><strong class="text-slate-900 dark:text-white">{{ $t['cashier_name'] }}</strong></div>
            <div class="flex justify-between"><span class="text-slate-500">Closed Timestamp:</span><span class="text-slate-700 dark:text-slate-300 font-mono">{{ $t['closed_at'] }}</span></div>
          </div>

          <table class="w-full text-left border-collapse text-xs border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden">
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
              <tr><td class="py-1.5 px-3 text-slate-600 dark:text-slate-300">Opening Cash Float</td><td class="py-1.5 px-3 text-right font-mono font-semibold">₱{{ $t['opening_float'] }}</td></tr>
              <tr><td class="py-1.5 px-3 text-slate-600 dark:text-slate-300">Expected Cash in Drawer</td><td class="py-1.5 px-3 text-right font-mono font-semibold">₱{{ $t['expected_cash'] }}</td></tr>
              <tr class="bg-emerald-50/50 dark:bg-emerald-950/20 font-bold"><td class="py-1.5 px-3 text-slate-900 dark:text-white">Physical Cash Counted</td><td class="py-1.5 px-3 text-right font-mono text-emerald-600 dark:text-emerald-400">₱{{ $t['actual_cash'] }}</td></tr>
              <tr><td class="py-1.5 px-3 text-slate-600 dark:text-slate-300">Drawer Cash Variance</td><td class="py-1.5 px-3 text-right font-mono font-bold {{ (float) str_replace(',', '', $t['cash_variance']) == 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">₱{{ $t['cash_variance'] }}</td></tr>
              <tr><td class="py-1.5 px-3 text-slate-600 dark:text-slate-300">Total Digital Collections</td><td class="py-1.5 px-3 text-right font-mono font-semibold">₱{{ $t['digital_collections'] }}</td></tr>
              <tr class="bg-blue-50/50 dark:bg-blue-950/20 font-bold"><td class="py-1.5 px-3 text-slate-900 dark:text-white">Total Shift Revenue</td><td class="py-1.5 px-3 text-right font-mono text-blue-600 dark:text-blue-400">₱{{ $t['total_collections'] }}</td></tr>
            </tbody>
          </table>

          <div class="rounded-xl border border-slate-100 bg-slate-50 p-2.5 text-xs text-slate-600 dark:border-slate-800 dark:bg-slate-950/60 dark:text-slate-400">
            <strong>Variance Reason:</strong> {{ $t['variance_reason'] ?? 'N/A' }}
          </div>

          <div class="grid grid-cols-2 gap-4 text-center text-[10px] pt-4 border-t border-slate-100 dark:border-slate-800">
            <div>
              <div class="border-b border-slate-300 dark:border-slate-700 pb-6 mb-1"></div>
              <span class="text-slate-500">Remitting Cashier Signature</span>
            </div>
            <div>
              <div class="border-b border-slate-300 dark:border-slate-700 pb-6 mb-1"></div>
              <span class="text-slate-500">Vault / Treasury Receiver</span>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
          <a href="{{ route('collection.cashier-desk') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 transition-all">
            Close
          </a>
          <button type="button" class="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-slate-800 dark:bg-slate-700 dark:hover:bg-slate-600 transition-all" onclick="window.print()">
            <i class="ph ph-printer"></i>
            Print Turnover Tag
          </button>
        </div>
      </div>
    </div>
  @endif

</div>
@endsection

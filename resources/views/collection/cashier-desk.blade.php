@extends('layouts.app')

@section('title', 'Cashier Desk & POS Collection Counter | FMS')
@section('module', 'collection')
@section('page', 'cashier-desk')

@section('content')
<div class="space-y-6">

  <!-- Page Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
    <div>
      <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
          Cashier POS Desk &amp; Payment Counter
        </h1>
    </div>

    <div class="flex items-center gap-2.5 flex-wrap">

      <button 
        type="button" 
        onclick="location.reload()" 
        class="inline-flex items-center gap-1.5 rounded-xl bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-700 transition-all"
      >
        <i class="ph-bold ph-arrow-counter-clockwise"></i>
        <span>Refresh</span>
      </button>

      @if($activeShift)
        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300">
          <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
          <span>Shift: <strong class="font-mono">{{ $activeShift->shift_code }}</strong> (OPEN)</span>
        </span>
        <button 
          type="button" 
          @click="$dispatch('open-modal', 'closeShiftModal')"
          class="inline-flex items-center gap-1.5 rounded-xl bg-amber-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-amber-700 ring-1 ring-amber-600/20 transition-all"
        >
          <i class="ph-bold ph-scales"></i>
          <span>Close Shift &amp; Turnover</span>
        </button>
      @else
        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-600/20 dark:bg-amber-950/40 dark:text-amber-300">
          <i class="ph-bold ph-warning"></i>
          <span>No Active Shift</span>
        </span>
        <button 
          type="button" 
          @click="$dispatch('open-modal', 'openShiftModal')"
          class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 ring-1 ring-emerald-600/20 transition-all"
        >
          <i class="ph-bold ph-play-circle"></i>
          <span>Open Shift</span>
        </button>
      @endif
    </div>
  </div>

  <!-- Alerts -->
  @if(session('success'))
    <div class="rounded-xl bg-emerald-50 p-4 text-xs font-medium text-emerald-800 ring-1 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <i class="ph-bold ph-check-circle text-lg text-emerald-600"></i>
        <span>{{ session('success') }}</span>
      </div>
    </div>
  @endif

  @if(session('error'))
    <div class="rounded-xl bg-rose-50 p-4 text-xs font-medium text-rose-800 ring-1 ring-rose-600/20 dark:bg-rose-950/40 dark:text-rose-300 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <i class="ph-bold ph-warning-circle text-lg text-rose-600"></i>
        <span>{{ session('error') }}</span>
      </div>
    </div>
  @endif

  <!-- Active Shift Drawer Metrics Summary Cards -->
  @if($activeShift)
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3.5">
      <x-stat-card 
        title="Assigned POS Terminal" 
        :value="$activeShift->terminal_name" 
        :isCurrency="false"
        icon="ph-desktop" 
        color="emerald" 
        subtitle="Active Hospital Station"
      />

      <x-stat-card 
        title="Opening Cash Float" 
        :value="$activeShift->opening_cash_float" 
        icon="ph-coins" 
        color="slate" 
        subtitle="Drawer start balance"
      />

      <x-stat-card 
        title="Expected Cash in Drawer" 
        :value="$activeShift->expected_cash" 
        icon="ph-vault" 
        color="emerald" 
        subtitle="Opening float + Cash collections"
      />

      <x-stat-card 
        title="Digital Inflows (Card/QR)" 
        :value="$activeShift->total_digital_collections" 
        icon="ph-credit-card" 
        color="blue" 
        subtitle="GCash, Maya, Card POS"
      />
    </div>
  @endif

  <!-- Main POS Counter Interface: 2-Column Responsive Layout -->
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
    <!-- Left Column: Outstanding Patient Copays Queue (Col 7) -->
    <div class="lg:col-span-7 rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 overflow-hidden dark:bg-slate-900 dark:ring-slate-800">
      <div class="p-5 border-b border-slate-200 dark:border-slate-800 space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <i class="ph-bold ph-receipt text-emerald-600 dark:text-emerald-400"></i>
            Outstanding Patient Copays
          </h2>
          <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300">
            {{ $pendingInvoices->total() }} Records
          </span>
        </div>

        <!-- Filter and Search Toolbar -->
        <form method="GET" action="{{ route('collection.cashier-desk') }}" class="space-y-3">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <!-- Admission Type Filter Tabs -->
            <div class="inline-flex rounded-xl bg-slate-100 p-1 dark:bg-slate-800">
              <a 
                href="{{ route('collection.cashier-desk', array_merge(request()->query(), ['admission_type' => ''])) }}" 
                class="px-3 py-1 rounded-lg text-xs font-semibold transition-all {{ empty($admissionType) ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-700 dark:text-white' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400' }}"
              >
                All
              </a>
              <a 
                href="{{ route('collection.cashier-desk', array_merge(request()->query(), ['admission_type' => 'Inpatient'])) }}" 
                class="px-3 py-1 rounded-lg text-xs font-semibold transition-all {{ $admissionType === 'Inpatient' ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-700 dark:text-white' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400' }}"
              >
                Inpatient
              </a>
              <a 
                href="{{ route('collection.cashier-desk', array_merge(request()->query(), ['admission_type' => 'Outpatient'])) }}" 
                class="px-3 py-1 rounded-lg text-xs font-semibold transition-all {{ $admissionType === 'Outpatient' ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-700 dark:text-white' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400' }}"
              >
                Outpatient
              </a>
            </div>

            <!-- Hide Zero-Balance Toggle -->
            <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-600 dark:text-slate-400">
              <input 
                type="checkbox" 
                name="hide_zero" 
                value="1" 
                {{ ($hideZero ?? true) ? 'checked' : '' }} 
                onchange="this.form.submit()" 
                class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-800"
              >
              <span>Hide Zero-Balance Bills</span>
            </label>
          </div>

          <!-- Search Input -->
          <div class="relative w-full">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
              <i class="ph ph-magnifying-glass text-base"></i>
            </div>
            <input 
              type="text" 
              name="q" 
              value="{{ $search }}" 
              placeholder="Search by Patient Name, MRN #, or Invoice #..." 
              style="outline: none !important;"
              class="w-full rounded-xl border-0 bg-slate-50 py-2 pl-10 pr-20 text-xs sm:text-sm text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-emerald-600 focus:outline-none focus:outline-0 outline-none dark:bg-slate-800 dark:text-white dark:ring-slate-700 transition-all"
            >
            <div class="absolute inset-y-0 right-1 flex items-center gap-1">
              <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-1 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 transition-all">
                Search
              </button>
              @if($search || $admissionType)
                <a href="{{ route('collection.cashier-desk') }}" class="rounded-lg px-2 py-1 text-xs font-semibold text-slate-500 hover:text-slate-700 dark:hover:text-white">
                  Reset
                </a>
              @endif
            </div>
          </div>
        </form>
      </div>

      <!-- Table of Invoices -->
      <div class="overflow-x-auto custom-scrollbar">
        <table class="w-full text-left text-sm text-slate-700 dark:text-slate-300">
          <thead class="border-b border-slate-200 bg-slate-50/75 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-400">
            <tr>
              <th class="py-3 px-4">Invoice #</th>
              <th class="py-3 px-4">Patient / Guarantor</th>
              <th class="py-3 px-4">Type</th>
              <th class="py-3 px-4 text-right font-mono">Net Copay Due</th>
              <th class="py-3 px-4 text-center">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse($pendingInvoices as $inv)
              @php
                $copayFormatted = number_format((float) $inv->patient_payable, 2, '.', '');
                $copayVal = (float) $copayFormatted;
                $isZeroCopay = ($copayVal <= 0.00);
              @endphp
              <tr class="transition-colors hover:bg-slate-50/50 dark:hover:bg-slate-800/40 {{ $isZeroCopay ? 'opacity-60' : '' }}">
                <td class="py-3.5 px-4">
                  <span class="font-mono font-semibold text-slate-900 dark:text-white">{{ $inv->invoice_number }}</span>
                  <div class="text-[11px] text-slate-400 font-mono">{{ $inv->invoice_date ? $inv->invoice_date->format('M d, Y') : '-' }}</div>
                </td>
                <td class="py-3.5 px-4">
                  <div class="font-semibold text-slate-900 dark:text-white">{{ $inv->patientAccount?->full_name ?? 'Patient' }}</div>
                  <div class="font-mono text-xs text-slate-400">{{ $inv->patientAccount?->patient_id_number ?? 'MRN-N/A' }}</div>
                </td>
                <td class="py-3.5 px-4 text-xs">
                  <span class="rounded bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                    {{ $inv->patientAccount?->admission_type ?? 'Outpatient' }}
                  </span>
                </td>
                <td class="py-3.5 px-4 text-right font-mono tabular-nums font-bold text-base {{ $isZeroCopay ? 'text-slate-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                  ₱{{ number_format($copayVal, 2) }}
                </td>
                <td class="py-3.5 px-4 text-center">
                  @if($isZeroCopay)
                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                      <i class="ph-bold ph-check"></i> Cleared
                    </span>
                  @else
                    <button 
                      type="button" 
                      @click="$dispatch('open-modal', 'payModal{{ $inv->id }}')"
                      class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 ring-1 ring-emerald-600/20 transition-all"
                    >
                      <i class="ph-bold ph-coins"></i>
                      <span>Settle</span>
                    </button>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="py-12 text-center text-sm text-slate-400">
                  <i class="ph ph-receipt text-3xl mb-2 block mx-auto text-slate-300"></i>
                  No outstanding patient copays found matching criteria.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="border-t border-slate-200 p-4 dark:border-slate-800">
        {{ $pendingInvoices->links() }}
      </div>
    </div>

    <!-- Right Column: Recent Official Receipts Register (Col 5) -->
    <div class="lg:col-span-5 rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 overflow-hidden dark:bg-slate-900 dark:ring-slate-800">
      <div class="p-5 border-b border-slate-200 dark:border-slate-800">
        <div class="flex items-center justify-between">
          <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <i class="ph-bold ph-check-circle text-emerald-600 dark:text-emerald-400"></i>
            Issued Official Receipts (OR)
          </h2>
          <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300">
            Today: {{ count($todayPayments) }}
          </span>
        </div>
        <p class="mt-1 text-xs text-slate-500">Real-time BIR EOPT sequential official receipt register</p>
      </div>

      <div class="overflow-x-auto custom-scrollbar">
        <table class="w-full text-left text-sm text-slate-700 dark:text-slate-300">
          <thead class="border-b border-slate-200 bg-slate-50/75 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-400">
            <tr>
              <th class="py-3 px-4">OR #</th>
              <th class="py-3 px-4">Payor / Patient</th>
              <th class="py-3 px-4 text-right font-mono">Amount</th>
              <th class="py-3 px-4 text-center">Print</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse($todayPayments as $pay)
              <tr class="transition-colors hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                <td class="py-3 px-4">
                  <span class="font-mono font-semibold text-emerald-700 dark:text-emerald-400 text-xs">
                    {{ $pay->officialReceipt?->or_number ?? 'OR-PENDING' }}
                  </span>
                  <div class="text-[11px] text-slate-400">{{ $pay->payment_method }}</div>
                </td>
                <td class="py-3 px-4">
                  <div class="font-medium text-xs text-slate-900 dark:text-white truncate max-w-[140px]">
                    {{ $pay->officialReceipt?->payor_name ?: ($pay->patientAccount?->full_name ?? 'Patient') }}
                  </div>
                  <div class="font-mono text-[10px] text-slate-400 truncate">{{ $pay->payment_reference }}</div>
                </td>
                <td class="py-3 px-4 text-right font-mono tabular-nums font-bold text-slate-900 dark:text-white">
                  ₱{{ number_format((float) $pay->amount, 2) }}
                </td>
                <td class="py-3 px-4 text-center">
                  <a 
                    href="{{ route('collection.receipts.print', $pay->id) }}" 
                    target="_blank" 
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-600 hover:bg-emerald-50 hover:text-emerald-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 transition-colors" 
                    title="Print BIR Official Receipt"
                  >
                    <i class="ph-bold ph-printer text-base"></i>
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="py-8 text-center text-xs text-slate-400">No collections posted today.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Bottom: POS Terminal Stations & Supervision Table -->
  
  <!-- Sub-Module Category Navigation Bar -->
  @include('partials.submodule-nav')

  <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 overflow-hidden dark:bg-slate-900 dark:ring-slate-800">
    <div class="p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
      <div>
        <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
          <i class="ph-bold ph-desktop text-emerald-600 dark:text-emerald-400"></i>
          All Hospital POS Stations &amp; Terminal Shifts
        </h3>
        <p class="text-xs text-slate-500">Live drawer balances, cashier custody handovers, and supervisory reconciliations</p>
      </div>
      <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
        {{ count($shifts ?? []) }} Stations
      </span>
    </div>

    <div class="overflow-x-auto custom-scrollbar">
      <table class="w-full text-left text-sm text-slate-700 dark:text-slate-300">
        <thead class="border-b border-slate-200 bg-slate-50/75 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-400">
          <tr>
            <th class="py-3.5 px-4">Shift Code</th>
            <th class="py-3.5 px-4">Station / Terminal</th>
            <th class="py-3.5 px-4">Cashier Officer</th>
            <th class="py-3.5 px-4 text-right font-mono">Opening Float (₱)</th>
            <th class="py-3.5 px-4 text-right font-mono">Drawer Expected / Counted</th>
            <th class="py-3.5 px-4">Shift Start</th>
            <th class="py-3.5 px-4 text-center">Status</th>
            <th class="py-3.5 px-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
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
            @endphp
            <tr class="transition-colors hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
              <td class="py-3.5 px-4 font-mono font-bold text-slate-900 dark:text-white">{{ $tId }}</td>
              <td class="py-3.5 px-4">
                <div class="font-semibold text-slate-900 dark:text-white">{{ $loc }}</div>
                <span class="text-xs text-slate-400">Hospital POS Counter</span>
              </td>
              <td class="py-3.5 px-4 font-medium text-slate-800 dark:text-slate-200">{{ $cashier }}</td>
              <td class="py-3.5 px-4 text-right font-mono text-slate-600 dark:text-slate-400">{{ $float }}</td>
              <td class="py-3.5 px-4 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ $cash }}</td>
              <td class="py-3.5 px-4 font-mono text-xs text-slate-500">{{ $start }}</td>
              <td class="py-3.5 px-4 text-center">
                <x-status-badge :status="$status" />
              </td>
              <td class="py-3.5 px-4 text-right">
                @if($status === 'CLOSED')
                  <form method="POST" action="{{ route('collection.shifts.reconcile', $t->id) }}" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-600/20 hover:bg-emerald-100 transition-all">
                      <i class="ph-bold ph-shield-check"></i>
                      <span>Reconcile</span>
                    </button>
                  </form>
                @elseif($status === 'RECONCILED')
                  <span class="inline-flex items-center gap-1 text-xs text-slate-400">
                    <i class="ph-bold ph-lock"></i> Reconciled
                  </span>
                @else
                  <span class="inline-flex items-center gap-1 text-xs text-emerald-600 dark:text-emerald-400 font-semibold">
                    <i class="ph-bold ph-activity"></i> Active
                  </span>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="py-8 text-center text-sm text-slate-400">No cashier shift records available.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

</div>

<!-- Payment Settle Modals for Invoices (One per open invoice) -->
@foreach($pendingInvoices as $inv)
  @php
    $copayFormatted = number_format((float) $inv->patient_payable, 2, '.', '');
    $copayVal = (float) $copayFormatted;
  @endphp
  @if($copayVal > 0)
    <x-modal 
      id="payModal{{ $inv->id }}" 
      title="Counter Settlement & Official Receipt" 
      subtitle="Process patient copay collection & issue BIR compliant receipt" 
      icon="ph-receipt" 
      iconVariant="emerald" 
      size="lg" 
      formAction="{{ route('collection.cashier-desk.collect') }}" 
      formMethod="POST" 
      submitText="Post & Issue BIR Receipt" 
      submitIcon="ph-check-circle"
      submitVariant="emerald"
    >
      <input type="hidden" name="invoice_id" value="{{ $inv->id }}">
      @if($activeShift)
        <input type="hidden" name="cashier_shift_id" value="{{ $activeShift->id }}">
      @endif

      <div 
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
              return Math.max(0, t - c).toFixed(2);
            }
            if (this.paymentMethod !== 'CASH') return '0.00';
            const s = parseFloat(this.settlementAmount) || 0;
            const t = parseFloat(this.amountTendered) || 0;
            return Math.max(0, t - s).toFixed(2);
          },
          get splitTotalSum() {
            const c = parseFloat(this.splitCashAmount) || 0;
            const d = parseFloat(this.splitDigitalAmount) || 0;
            return c + d;
          },
          get isSplitValid() {
            if (this.paymentMethod !== 'SPLIT_PAYMENT') return true;
            const s = parseFloat(this.settlementAmount) || 0;
            return Math.abs(this.splitTotalSum - s) < 0.01 && this.splitTotalSum > 0;
          },
          get isUnderTendered() {
            if (this.paymentMethod === 'SPLIT_PAYMENT') return !this.isSplitValid;
            if (this.paymentMethod !== 'CASH') return false;
            const s = parseFloat(this.settlementAmount) || 0;
            const t = parseFloat(this.amountTendered) || 0;
            return t < s;
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
        }" 
        class="space-y-4"
      >
        <!-- Patient Summary Card -->
        <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200 dark:bg-slate-800/60 dark:ring-slate-700">
          <div class="flex justify-between items-center text-xs text-slate-500 mb-1">
            <span>Patient:</span>
            <strong class="text-slate-900 dark:text-white text-sm">{{ $inv->patientAccount?->full_name ?? 'Patient' }}</strong>
          </div>
          <div class="flex justify-between items-center text-xs text-slate-500 mb-2">
            <span>Invoice Reference:</span>
            <span class="font-mono text-slate-700 dark:text-slate-300 font-semibold">{{ $inv->invoice_number }}</span>
          </div>
          <div class="flex justify-between items-center pt-2 border-t border-slate-200 dark:border-slate-700">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Net Copay Due:</span>
            <span class="font-mono text-xl font-bold text-emerald-600 dark:text-emerald-400">₱{{ number_format($copayVal, 2) }}</span>
          </div>
        </div>

        <!-- Settlement Amount -->
        <div>
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">
            Settlement Amount (₱) <span class="text-rose-500">*</span>
          </label>
          <input 
            type="number" 
            step="0.01" 
            min="0.01" 
            max="{{ $copayFormatted }}" 
            name="amount" 
            x-model="settlementAmount" 
            required 
            class="w-full rounded-xl border-0 bg-slate-50 py-2.5 px-3 font-mono text-base font-bold text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
          >
        </div>

        <!-- Payment Method -->
        <div>
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">
            Payment Channel <span class="text-rose-500">*</span>
          </label>
          <select 
            name="payment_method" 
            x-model="paymentMethod" 
            @change="onChannelChange()" 
            required 
            class="w-full rounded-xl border-0 bg-slate-50 py-2.5 px-3 text-sm font-semibold text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
          >
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

        <!-- Single Cash Inputs -->
        <div class="grid grid-cols-2 gap-3" x-show="paymentMethod === 'CASH'">
          <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">
              Amount Tendered (₱) <span class="text-rose-500">*</span>
            </label>
            <input 
              type="number" 
              step="0.01" 
              min="0" 
              name="tendered_amount" 
              x-model="amountTendered" 
              class="w-full rounded-xl border-0 bg-slate-50 py-2 px-3 font-mono text-sm font-bold text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700 text-right"
            >
          </div>
          <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">
              Change Due (₱)
            </label>
            <div class="w-full rounded-xl bg-slate-100 py-2 px-3 font-mono text-sm font-bold text-emerald-600 dark:bg-slate-800 dark:text-emerald-400 text-right ring-1 ring-slate-200 dark:ring-slate-700">
              ₱<span x-text="changeAmount"></span>
            </div>
          </div>
        </div>

        <!-- Split Payment Breakdown -->
        <div x-show="paymentMethod === 'SPLIT_PAYMENT'" class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200 dark:bg-slate-800/60 dark:ring-slate-700 space-y-3">
          <span class="text-xs font-bold uppercase tracking-wider text-slate-500 block">Split Tender Allocation</span>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-[11px] text-slate-400 mb-1">Tender 1: Cash Amount</label>
              <input type="number" step="0.01" name="split_cash_amount" x-model="splitCashAmount" class="w-full rounded-lg border-0 bg-white py-1.5 px-2 text-right font-mono text-xs ring-1 ring-slate-300 dark:bg-slate-800 dark:text-white">
            </div>
            <div>
              <label class="block text-[11px] text-slate-400 mb-1">Cash Tendered</label>
              <input type="number" step="0.01" x-model="splitCashTendered" class="w-full rounded-lg border-0 bg-white py-1.5 px-2 text-right font-mono text-xs ring-1 ring-slate-300 dark:bg-slate-800 dark:text-white">
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-[11px] text-slate-400 mb-1">Tender 2: Digital Channel</label>
              <select name="split_digital_channel" x-model="splitDigitalChannel" class="w-full rounded-lg border-0 bg-white py-1.5 px-2 text-xs ring-1 ring-slate-300 dark:bg-slate-800 dark:text-white">
                <option value="CREDIT_CARD">Credit Card POS</option>
                <option value="DEBIT_CARD">Debit Card POS</option>
                <option value="GCASH">GCash E-Wallet</option>
                <option value="MAYA">Maya Wallet</option>
                <option value="BANK_TRANSFER">Bank Transfer</option>
              </select>
            </div>
            <div>
              <label class="block text-[11px] text-slate-400 mb-1">Digital Amount</label>
              <input type="number" step="0.01" name="split_digital_amount" x-model="splitDigitalAmount" class="w-full rounded-lg border-0 bg-white py-1.5 px-2 text-right font-mono text-xs ring-1 ring-slate-300 dark:bg-slate-800 dark:text-white">
            </div>
          </div>
          <div class="pt-2 border-t border-slate-200 dark:border-slate-700 flex justify-between text-xs">
            <span>Cash Change: <strong class="font-mono text-emerald-600">₱<span x-text="changeAmount"></span></strong></span>
            <span :class="isSplitValid ? 'text-emerald-600 font-semibold' : 'text-rose-600 font-semibold'">
              <span x-text="isSplitValid ? '✓ Split Sum Matches' : '⚠️ Allocation Mismatch'"></span>
            </span>
          </div>
        </div>

        <!-- Gateway Ref -->
        <div x-show="paymentMethod !== 'CASH' && paymentMethod !== 'SPLIT_PAYMENT'">
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Transaction / Auth Code</label>
          <input type="text" name="gateway_transaction_id" placeholder="e.g. GCash Ref # or POS Auth Code" class="w-full rounded-xl border-0 bg-slate-50 py-2 px-3 font-mono text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700">
        </div>

        <!-- Payor Name -->
        <div>
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Payor Legal Name (for BIR OR)</label>
          <input type="text" name="payor_name" value="{{ $inv->patientAccount?->full_name }}" placeholder="Payor full name" class="w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700">
        </div>

        <!-- Notes -->
        <div>
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Receipt Remarks (Optional)</label>
          <input type="text" name="notes" placeholder="Settlement memo..." class="w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700">
        </div>
      </div>
    </x-modal>
  @endif
@endforeach

<!-- Modal: Open Terminal Shift -->
<x-modal 
  id="openShiftModal" 
  title="Open Cashier Terminal Shift" 
  subtitle="Assign station and opening cash drawer float" 
  icon="ph-play-circle" 
  iconVariant="emerald" 
  size="md" 
  formAction="{{ route('collection.shifts.open') }}" 
  formMethod="POST" 
  submitText="Start Shift Now" 
  submitIcon="ph-check"
  submitVariant="emerald"
>
  <div class="space-y-4">
    <div>
      <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Select POS Station <span class="text-rose-500">*</span></label>
      <select name="terminal_name" required class="w-full rounded-xl border-0 bg-slate-50 py-2.5 px-3 text-sm font-semibold text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700">
        <option value="POS-MAIN-01 (Main Lobby)">POS-MAIN-01 (Main Lobby)</option>
        <option value="POS-ER-01 (Emergency Room)">POS-ER-01 (Emergency Room)</option>
        <option value="POS-PHARM-01 (Pharmacy Central)">POS-PHARM-01 (Pharmacy Central)</option>
        <option value="POS-OPD-01 (Outpatient Consultation)">POS-OPD-01 (Outpatient Consultation)</option>
      </select>
    </div>
    <div>
      <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Opening Cash Float (₱) <span class="text-rose-500">*</span></label>
      <input type="number" step="0.01" min="0" name="opening_cash_float" value="5000.00" required class="w-full rounded-xl border-0 bg-slate-50 py-2.5 px-3 font-mono text-base font-bold text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700 text-right">
      <span class="text-xs text-slate-400 mt-1 block">Physical petty cash drawer assigned at start of shift.</span>
    </div>
  </div>
</x-modal>

<!-- Modal: Close Shift & Turnover -->
@if($activeShift)
<x-modal 
  id="closeShiftModal" 
  title="Close Shift &amp; Drawer Turnover" 
  subtitle="Reconcile physical cash drawer with system expected balance" 
  icon="ph-scales" 
  iconVariant="amber" 
  size="md" 
  formAction="{{ route('collection.shifts.close') }}" 
  formMethod="POST" 
  submitText="Close &amp; Generate Turnover" 
  submitIcon="ph-lock" 
  submitVariant="amber"
>
  <input type="hidden" name="shift_id" value="{{ $activeShift->id }}">

  <div 
    x-data="{
      expected: {{ (float) $activeShift->expected_cash }},
      actual: 0.00,
      get variance() {
        return (parseFloat(this.actual) || 0) - this.expected;
      }
    }" 
    class="space-y-4"
  >
    <!-- Shift Breakdown Card -->
    <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200 dark:bg-slate-800/60 dark:ring-slate-700 space-y-2 text-xs">
      <div class="flex justify-between"><span class="text-slate-500">Terminal:</span><strong class="text-slate-900 dark:text-white">{{ $activeShift->terminal_name }}</strong></div>
      <div class="flex justify-between"><span class="text-slate-500">Shift Code:</span><span class="font-mono font-bold text-emerald-600">{{ $activeShift->shift_code }}</span></div>
      <div class="flex justify-between"><span class="text-slate-500">Opening Cash Float:</span><span class="font-mono">₱{{ number_format((float) $activeShift->opening_cash_float, 2) }}</span></div>
      <div class="flex justify-between"><span class="text-slate-500">Digital / Card Collections:</span><span class="font-mono">₱{{ number_format((float) $activeShift->total_digital_collections, 2) }}</span></div>
      <div class="pt-2 border-t border-slate-200 dark:border-slate-700 flex justify-between font-bold text-sm">
        <span>System Expected Cash:</span>
        <span class="font-mono text-emerald-600 dark:text-emerald-400">₱{{ number_format((float) $activeShift->expected_cash, 2) }}</span>
      </div>
    </div>

    <div>
      <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">
        Actual Physical Cash Counted (₱) <span class="text-rose-500">*</span>
      </label>
      <input 
        type="number" 
        step="0.01" 
        min="0" 
        name="actual_cash_counted" 
        x-model.number="actual" 
        required 
        class="w-full rounded-xl border-0 bg-slate-50 py-2.5 px-3 font-mono text-base font-bold text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700 text-right"
      >
    </div>

    <div>
      <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Drawer Variance</label>
      <div 
        class="w-full rounded-xl py-2 px-3 font-mono text-base font-bold text-right ring-1"
        :class="variance === 0 ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : (variance < 0 ? 'bg-rose-50 text-rose-700 ring-rose-200' : 'bg-blue-50 text-blue-700 ring-blue-200')"
      >
        <span x-text="(variance >= 0 ? '+' : '') + '₱' + variance.toFixed(2)"></span>
      </div>
    </div>

    <div>
      <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Variance Reason (Optional)</label>
      <textarea name="variance_reason" rows="2" placeholder="Explain any cash overage or shortage..." class="w-full rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"></textarea>
    </div>
  </div>
</x-modal>
@endif

<!-- Printable Shift Turnover Bag Tag Summary Modal -->
@if(session('turnover_summary'))
  @php $t = session('turnover_summary'); @endphp
  <x-modal 
    id="turnoverPrintModal" 
    title="Physical Cash Turnover Bag Tag" 
    subtitle="Shift custody reconciliation tag • BIR CAS Audited" 
    icon="ph-tag" 
    iconVariant="emerald" 
    size="md" 
    :showFooter="true"
  >
    <div id="printableTurnoverArea" class="space-y-4 text-xs font-mono">
      <div class="text-center border-b border-slate-200 pb-3">
        <h4 class="font-bold text-sm uppercase text-slate-900 dark:text-white">Financial Management System</h4>
        <span class="text-slate-500 text-[11px]">Cashier Shift Custody Turnover Slip &bull; BIR CAS Audited</span>
      </div>

      <div class="space-y-1.5 text-slate-700 dark:text-slate-300">
        <div class="flex justify-between"><span>Shift Code:</span><strong class="text-emerald-600">{{ $t['shift_code'] }}</strong></div>
        <div class="flex justify-between"><span>Terminal:</span><strong>{{ $t['terminal_name'] }}</strong></div>
        <div class="flex justify-between"><span>Cashier Officer:</span><strong>{{ $t['cashier_name'] }}</strong></div>
        <div class="flex justify-between"><span>Closed Timestamp:</span><span>{{ $t['closed_at'] }}</span></div>
      </div>

      <table class="w-full border-collapse border border-slate-200 text-xs">
        <tr class="border-b border-slate-200"><td class="p-2">Opening Cash Float</td><td class="p-2 text-right">₱{{ $t['opening_float'] }}</td></tr>
        <tr class="border-b border-slate-200"><td class="p-2">Expected Cash in Drawer</td><td class="p-2 text-right">₱{{ $t['expected_cash'] }}</td></tr>
        <tr class="border-b border-slate-200 bg-slate-50 font-bold"><td class="p-2">Physical Cash Counted</td><td class="p-2 text-right text-emerald-600">₱{{ $t['actual_cash'] }}</td></tr>
        <tr class="border-b border-slate-200"><td class="p-2">Drawer Cash Variance</td><td class="p-2 text-right font-bold {{ (float) str_replace(',', '', $t['cash_variance']) == 0 ? 'text-emerald-600' : 'text-rose-600' }}">₱{{ $t['cash_variance'] }}</td></tr>
        <tr class="border-b border-slate-200"><td class="p-2">Digital Collections</td><td class="p-2 text-right">₱{{ $t['digital_collections'] }}</td></tr>
        <tr class="bg-emerald-50 font-bold text-emerald-900"><td class="p-2">Total Shift Revenue</td><td class="p-2 text-right">₱{{ $t['total_collections'] }}</td></tr>
      </table>

      @if(!empty($t['variance_reason']))
        <div class="rounded-lg bg-slate-50 p-2.5 ring-1 ring-slate-200 text-[11px]">
          <strong>Variance Reason:</strong> {{ $t['variance_reason'] }}
        </div>
      @endif

      <div class="grid grid-cols-2 gap-4 text-center pt-4 border-t border-slate-200 text-[11px]">
        <div>
          <div class="border-b border-slate-300 pb-8 mb-1"></div>
          <span>Remitting Cashier</span>
        </div>
        <div>
          <div class="border-b border-slate-300 pb-8 mb-1"></div>
          <span>Vault Receiver</span>
        </div>
      </div>
    </div>

    <x-slot:footer>
      <button type="button" @click="$dispatch('close-modal', 'turnoverPrintModal')" class="rounded-xl px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100">Close</button>
      <button type="button" onclick="window.print()" class="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-slate-800">
        <i class="ph-bold ph-printer"></i>
        <span>Print Turnover Tag</span>
      </button>
    </x-slot:footer>
  </x-modal>
@endif

@endsection

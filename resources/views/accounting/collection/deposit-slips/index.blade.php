@extends('layouts.app')

@section('title', 'Batch Deposit Slips - Collection Management | FMS')
@section('module', 'collection')
@section('page', 'deposit-slips')

@section('content')
<div class="space-y-6" x-data="{
  init() {
    this.$watch('createSlipOpen', val => {
      document.body.classList.toggle('overflow-hidden', val);
      document.body.classList.toggle('modal-open', val);
    });
  },
  createSlipOpen: false,
  slipShiftId: '',
  slipCashAmount: '0.00',
  prepareDepositForShift(shiftId, shiftCode, amount) {
    this.slipShiftId = shiftId;
    this.slipCashAmount = parseFloat(amount).toFixed(2);
    this.createSlipOpen = true;
  },
  openNewSlip() {
    this.slipShiftId = '';
    this.slipCashAmount = '0.00';
    this.createSlipOpen = true;
  },
  closeSlip() {
    this.createSlipOpen = false;
  }
}" @keydown.escape.window="closeSlip()">

  {{-- Page Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <div class="flex items-center gap-2 text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">
        <a href="{{ route('accounting.dashboard') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">Overview</a>
        <i class="ph ph-caret-right text-[10px]"></i>
        <a href="{{ route('collection.receipts') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">Collection</a>
        <i class="ph ph-caret-right text-[10px]"></i>
        <span class="text-slate-900 dark:text-slate-200 font-semibold">Deposit Slips</span>
      </div>
      <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
        Batch Deposit Slips &amp; Custody Handover
      </h1>
    </div>
    <div class="flex flex-wrap items-center gap-2.5">
      <button class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 transition-all" type="button" onclick="window.print()">
        <i class="ph ph-printer"></i>
        Print Manifest
      </button>
      <button id="btnCreateSlip" @click="openNewSlip()" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 transition-all" type="button">
        <i class="ph ph-plus-circle"></i>
        Create Deposit Slip
      </button>
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
    {{-- Prepared Slips --}}
    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
      <div class="flex items-center justify-between">
        <span class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Prepared Deposit Slips</span>
        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
          <i class="ph-duotone ph-path text-lg"></i>
        </div>
      </div>
      <div class="mt-2">
        <span class="text-2xl font-bold font-mono text-slate-900 dark:text-white">{{ count($deposits ?? []) }}</span>
      </div>
      <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Batch deposit slips generated</p>
    </div>

    {{-- Total Vault Deposits --}}
    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
      <div class="flex items-center justify-between">
        <span class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Vault Deposits</span>
        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
          <i class="ph-duotone ph-money text-lg"></i>
        </div>
      </div>
      <div class="mt-2">
        <span class="text-2xl font-bold font-mono text-emerald-600 dark:text-emerald-400">₱{{ number_format((float) ($totalDeposits ?? 0), 2) }}</span>
      </div>
      <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Cash &amp; checks prepared</p>
    </div>

    {{-- Closed Shifts Pending Remittance --}}
    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
      <div class="flex items-center justify-between">
        <span class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Closed Shifts Pending</span>
        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400">
          <i class="ph-duotone ph-vault text-lg"></i>
        </div>
      </div>
      <div class="mt-2">
        <span class="text-2xl font-bold font-mono text-amber-600 dark:text-amber-400">{{ count($closedShifts ?? []) }}</span>
      </div>
      <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Shifts awaiting deposit batching</p>
    </div>
  </div>


{{-- Section: Closed Shifts Ready for Custody Handover --}}
  <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800 overflow-hidden">
    <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
      <div>
        <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
          <i class="ph-fill ph-vault text-amber-500"></i>
          Closed Shifts Ready for Bank Remittance
        </h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Terminal cashiers with finalized physical counts awaiting batch deposit custody slip.</p>
      </div>
      <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-mono font-semibold text-amber-800 dark:bg-amber-900/60 dark:text-amber-200">
        {{ count($closedShifts ?? []) }} Awaiting
      </span>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse text-xs">
        <thead>
          <tr class="border-b border-slate-200/80 bg-slate-50/75 dark:border-slate-800 dark:bg-slate-950/50">
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Shift Code</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Terminal</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Cashier Officer</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Closed Timestamp</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px] text-right">Actual Cash Counted</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px] text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
          @forelse($closedShifts ?? [] as $cs)
            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
              <td class="py-3 px-4 font-mono font-semibold text-emerald-600 dark:text-emerald-400">{{ $cs->shift_code }}</td>
              <td class="py-3 px-4 text-slate-900 dark:text-white font-medium">{{ $cs->terminal_name }}</td>
              <td class="py-3 px-4 text-slate-700 dark:text-slate-300">{{ $cs->cashier?->name ?? 'Cashier' }}</td>
              <td class="py-3 px-4 font-mono text-slate-500 dark:text-slate-400">{{ $cs->closed_at ? $cs->closed_at->format('M d, Y h:i A') : '-' }}</td>
              <td class="py-3 px-4 text-right font-mono tabular-nums font-semibold text-emerald-600 dark:text-emerald-400">₱{{ number_format((float) $cs->actual_cash_counted, 2) }}</td>
              <td class="py-3 px-4 text-right">
                <button @click="prepareDepositForShift('{{ $cs->id }}', '{{ $cs->shift_code }}', '{{ $cs->actual_cash_counted }}')" class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 transition-all" type="button">
                  <i class="ph ph-plus-circle"></i>
                  Prepare Deposit
                </button>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center py-6 text-slate-500 dark:text-slate-400">
                No closed cashier shifts pending deposit.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- Section: Prepared Batch Deposit Slips --}}
  <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800 overflow-hidden">
    <div class="p-5 border-b border-slate-100 dark:border-slate-800">
      <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
        <i class="ph ph-receipt text-emerald-600 dark:text-emerald-400"></i>
        Batch Deposit Slips History
      </h3>
      <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Historical records of prepared bank deposit slips and custody handovers.</p>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse text-xs">
        <thead>
          <tr class="border-b border-slate-200/80 bg-slate-50/75 dark:border-slate-800 dark:bg-slate-950/50">
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Deposit Ref #</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Date</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Depository Bank</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Shift Ref</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px] text-right">Cash Amount</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px] text-right">Check Amount</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px] text-right">Total Deposited</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px] text-center">Status</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
          @forelse($deposits ?? [] as $d)
            @php
              $st = $d->status;
              $badge = match($st) {
                'PREPARED' => 'bg-slate-100 text-slate-700 ring-slate-700/10 dark:bg-slate-800 dark:text-slate-300',
                'IN_TRANSIT' => 'bg-amber-50 text-amber-700 ring-amber-700/10 dark:bg-amber-950/60 dark:text-amber-400',
                'DEPOSITED', 'CLEARED' => 'bg-emerald-50 text-emerald-700 ring-emerald-700/10 dark:bg-emerald-950/60 dark:text-emerald-400',
                'RECONCILED' => 'bg-blue-50 text-blue-700 ring-blue-700/10 dark:bg-blue-950/60 dark:text-blue-400',
                default => 'bg-slate-100 text-slate-700 dark:bg-slate-800'
              };
            @endphp
            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
              <td class="py-3 px-4 font-mono font-semibold text-emerald-600 dark:text-emerald-400">{{ $d->deposit_reference }}</td>
              <td class="py-3 px-4 font-mono text-slate-600 dark:text-slate-300 whitespace-nowrap">{{ $d->deposit_date ? $d->deposit_date->format('M d, Y') : '-' }}</td>
              <td class="py-3 px-4">
                <div class="font-semibold text-slate-900 dark:text-white">{{ $d->bankAccount?->bank_name ?? 'Operational Bank' }}</div>
                <div class="text-[10px] text-slate-400 font-mono">{{ $d->bankAccount?->account_number }}</div>
              </td>
              <td class="py-3 px-4 font-mono text-slate-500 dark:text-slate-400 text-[11px]">{{ $d->cashierShift?->shift_code ?? 'Manual' }}</td>
              <td class="py-3 px-4 text-right font-mono tabular-nums text-slate-600 dark:text-slate-300">₱{{ number_format((float) $d->cash_amount, 2) }}</td>
              <td class="py-3 px-4 text-right font-mono tabular-nums text-slate-600 dark:text-slate-300">₱{{ number_format((float) $d->check_amount, 2) }}</td>
              <td class="py-3 px-4 text-right font-mono tabular-nums font-semibold text-emerald-600 dark:text-emerald-400">₱{{ number_format((float) $d->total_deposited, 2) }}</td>
              <td class="py-3 px-4 text-center">
                <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset {{ $badge }}">
                  {{ $st }}
                </span>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center py-8 text-slate-500 dark:text-slate-400">
                No deposit slips generated yet.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if(method_exists($deposits, 'links'))
      <div class="p-4 border-t border-slate-100 dark:border-slate-800">
        {{ $deposits->links() }}
      </div>
    @endif
  </div>

  {{-- Modal: Create Deposit Slip --}}
  <template x-teleport="body">
  <div x-show="createSlipOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div x-show="createSlipOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-950/60 backdrop-blur-md transition-opacity" @click="closeSlip()"></div>

    <div class="fixed inset-0 z-10 w-screen overflow-y-auto p-4 sm:p-6 md:p-20 flex min-h-full items-center justify-center pointer-events-none">
      <div x-show="createSlipOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" @click.outside="closeSlip()" class="pointer-events-auto relative mx-auto w-full max-w-lg transform rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800 transition-all text-left">

        <form method="POST" action="{{ route('collection.bank-deposits.store') }}" id="createSlipForm">
          @csrf
          <input type="hidden" name="cashier_shift_id" :value="slipShiftId">

          <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
            <div class="flex items-center gap-2.5">
              <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                <i class="ph-duotone ph-plus-circle text-xl"></i>
              </div>
              <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white" id="modal-title">
                  Create Bank Deposit Slip
                </h3>
                <p class="text-[11px] text-slate-400">Sealed cash/check custody remittance</p>
              </div>
            </div>
            <button @click="closeSlip()" type="button" class="rounded-lg p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
              <i class="ph ph-x text-base"></i>
            </button>
          </div>

          <div class="py-4 space-y-4">
            <div>
              <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                Depository Bank Account <span class="text-rose-500">*</span>
              </label>
              <select name="bank_account_id" class="block w-full rounded-xl border border-slate-200 bg-white py-2 px-3 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-800 dark:bg-slate-950 dark:text-white" required>
                @foreach($bankAccounts ?? [] as $ba)
                  <option value="{{ $ba->id }}">{{ $ba->bank_name }} - {{ $ba->account_name }} ({{ $ba->account_number }})</option>
                @endforeach
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                Deposit Date <span class="text-rose-500">*</span>
              </label>
              <input type="date" name="deposit_date" class="block w-full rounded-xl border border-slate-200 bg-white py-2 px-3 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-800 dark:bg-slate-950 dark:text-white" value="{{ date('Y-m-d') }}" required>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                  Cash Amount (₱) <span class="text-rose-500">*</span>
                </label>
                <input type="number" step="0.01" min="0" name="cash_amount" x-model="slipCashAmount" class="block w-full rounded-xl border border-slate-200 bg-white py-2 px-3 text-xs font-mono text-right text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-800 dark:bg-slate-950 dark:text-white" required>
              </div>
              <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                  Check Amount (₱)
                </label>
                <input type="number" step="0.01" min="0" name="check_amount" class="block w-full rounded-xl border border-slate-200 bg-white py-2 px-3 text-xs font-mono text-right text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-800 dark:bg-slate-950 dark:text-white" value="0.00">
              </div>
            </div>
          </div>

          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
            <button @click="closeSlip()" type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 transition-all">
              Cancel
            </button>
            <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 transition-all">
              <i class="ph ph-check"></i>
              Save Deposit Slip
            </button>
          </div>
        </form>

      </div>
    </div>
  </div>
  </template>

</div>
@endsection

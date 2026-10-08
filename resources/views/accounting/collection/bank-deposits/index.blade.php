@extends('layouts.app')

@section('title', 'Bank Deposits - Collection Management | FMS')
@section('module', 'collection')
@section('page', 'bank-deposits')

@section('content')
<div class="space-y-6" x-data="{
  init() {
    this.$watch('createModalOpen', () => this.syncBodyLock());
    this.$watch('clearModalOpen', () => this.syncBodyLock());
    this.$watch('rejectModalOpen', () => this.syncBodyLock());
  },
  syncBodyLock() {
    const anyOpen = this.createModalOpen || this.clearModalOpen || this.rejectModalOpen;
    document.body.classList.toggle('overflow-hidden', anyOpen);
    document.body.classList.toggle('modal-open', anyOpen);
  },
  createModalOpen: false,
  clearModalOpen: false,
  rejectModalOpen: false,
  clearId: null,
  clearRef: '',
  clearAmount: '',
  rejectId: null,
  rejectRef: '',
  openClear(id, ref, amount) {
    this.clearId = id;
    this.clearRef = ref;
    this.clearAmount = amount;
    this.clearModalOpen = true;
  },
  openReject(id, ref) {
    this.rejectId = id;
    this.rejectRef = ref;
    this.rejectModalOpen = true;
  }
}" @keydown.escape.window="createModalOpen = false; clearModalOpen = false; rejectModalOpen = false">

  {{-- Page Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <div class="flex items-center gap-2 text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">
        <a href="{{ route('accounting.dashboard') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">Overview</a>
        <i class="ph ph-caret-right text-[10px]"></i>
        <a href="{{ route('collection.receipts') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">Collection</a>
        <i class="ph ph-caret-right text-[10px]"></i>
        <span class="text-slate-900 dark:text-slate-200 font-semibold">Bank Deposits</span>
      </div>
      <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
        Bank Deposits Log &amp; Verification
      </h1>
    </div>
    <div class="flex flex-wrap items-center gap-2.5">
      <a href="{{ route('collection.deposit-slips') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 transition-all">
        <i class="ph ph-path"></i>
        Batch Deposit Slips
      </a>
      <button id="btnCreateDeposit" @click="createModalOpen = true" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 transition-all" type="button">
        <i class="ph ph-plus-circle"></i>
        New Bank Deposit
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
    {{-- Total Bank Deposits --}}
    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
      <div class="flex items-center justify-between">
        <span class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Bank Deposits</span>
        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
          <i class="ph-duotone ph-bank text-lg"></i>
        </div>
      </div>
      <div class="mt-2">
        <span class="text-2xl font-bold font-mono text-emerald-600 dark:text-emerald-400">₱{{ number_format((float) ($totalDeposits ?? 0), 2) }}</span>
      </div>
      <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Cleared &amp; verified remittances</p>
    </div>

    {{-- Total Records --}}
    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
      <div class="flex items-center justify-between">
        <span class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Records</span>
        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
          <i class="ph-duotone ph-receipt text-lg"></i>
        </div>
      </div>
      <div class="mt-2">
        <span class="text-2xl font-bold font-mono text-slate-900 dark:text-white">{{ count($deposits ?? []) }}</span>
      </div>
      <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Logged depository remittances</p>
    </div>

    {{-- Pending Clearance --}}
    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
      <div class="flex items-center justify-between">
        <span class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pending Teller Clearance</span>
        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400">
          <i class="ph-duotone ph-clock text-lg"></i>
        </div>
      </div>
      <div class="mt-2">
        @php
          $pendingCnt = ($deposits ?? collect())->whereIn('status', ['PREPARED', 'IN_TRANSIT'])->count();
        @endphp
        <span class="text-2xl font-bold font-mono text-amber-600 dark:text-amber-400">{{ $pendingCnt }}</span>
      </div>
      <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">In transit / awaiting teller stamp</p>
    </div>
  </div>


{{-- Data Table Card --}}
  <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800 overflow-hidden">
    <div class="p-5 border-b border-slate-100 dark:border-slate-800">
      <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
        <i class="ph ph-bank text-emerald-600 dark:text-emerald-400"></i>
        Bank Deposit &amp; Clearing Register
      </h3>
      <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Depository bank records and official teller machine clearance logs.</p>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse text-xs">
        <thead>
          <tr class="border-b border-slate-200/80 bg-slate-50/75 dark:border-slate-800 dark:bg-slate-950/50">
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Deposit Ref #</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Deposit Date</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Bank Account</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Cashier Shift</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px] text-right">Cash / Check Amount</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px] text-right">Total Deposited</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Bank Reference / Teller</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px] text-center">Status</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px] text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
          @forelse($deposits ?? [] as $d)
            @php
              $st = $d->status;
              $isCleared = in_array($st, ['DEPOSITED', 'CLEARED', 'RECONCILED']);
              $badge = $isCleared 
                ? 'bg-emerald-50 text-emerald-700 ring-emerald-700/10 dark:bg-emerald-950/60 dark:text-emerald-400' 
                : 'bg-amber-50 text-amber-700 ring-amber-700/10 dark:bg-amber-950/60 dark:text-amber-400';
            @endphp
            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
              <td class="py-3 px-4 font-mono font-semibold text-emerald-600 dark:text-emerald-400">{{ $d->deposit_reference }}</td>
              <td class="py-3 px-4 font-mono text-slate-600 dark:text-slate-300 whitespace-nowrap">{{ $d->deposit_date ? $d->deposit_date->format('M d, Y') : '-' }}</td>
              <td class="py-3 px-4">
                <div class="font-semibold text-slate-900 dark:text-white">{{ $d->bankAccount?->bank_name ?? 'Bank Account' }}</div>
                <div class="text-[10px] text-slate-400 font-mono">{{ $d->bankAccount?->account_number }}</div>
              </td>
              <td class="py-3 px-4 font-mono text-slate-500 dark:text-slate-400 text-[11px]">{{ $d->cashierShift?->shift_code ?? '-' }}</td>
              <td class="py-3 px-4 text-right font-mono text-slate-500 dark:text-slate-400 text-[11px]">
                ₱{{ number_format((float) $d->cash_amount, 2) }} / ₱{{ number_format((float) $d->check_amount, 2) }}
              </td>
              <td class="py-3 px-4 text-right font-mono tabular-nums font-semibold text-emerald-600 dark:text-emerald-400">₱{{ number_format((float) $d->total_deposited, 2) }}</td>
              <td class="py-3 px-4">
                @if($d->bank_reference_number)
                  <span class="font-mono text-slate-900 dark:text-white font-medium block">{{ $d->bank_reference_number }}</span>
                  <span class="text-[10px] text-slate-400">{{ $d->validated_by_teller }}</span>
                @else
                  <span class="text-slate-400 italic text-[11px]">Pending validation</span>
                @endif
              </td>
              <td class="py-3 px-4 text-center">
                <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset {{ $badge }}">
                  {{ $st }}
                </span>
              </td>
              <td class="py-3 px-4 text-right">
                @if(! $isCleared)
                  <div class="inline-flex items-center gap-1.5">
                    <button @click="openClear('{{ $d->id }}', '{{ $d->deposit_reference }}', '{{ number_format((float) $d->total_deposited, 2) }}')" class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 transition-all" type="button" title="Validate & Clear Bank Deposit">
                      <i class="ph ph-check-circle"></i>
                      Clear
                    </button>
                    <button @click="openReject('{{ $d->id }}', '{{ $d->deposit_reference }}')" class="inline-flex items-center justify-center rounded-lg border border-rose-200 bg-white p-1 text-rose-600 shadow-sm hover:bg-rose-50 dark:border-rose-900/50 dark:bg-slate-900 dark:text-rose-400 dark:hover:bg-rose-950/30 transition-all" type="button" title="Reject Deposit Slip">
                      <i class="ph ph-x-circle text-xs"></i>
                    </button>
                  </div>
                @else
                  <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400">
                    <i class="ph-fill ph-check-circle"></i> GL Posted
                  </span>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="text-center py-8 text-slate-500 dark:text-slate-400">
                No bank deposits logged.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if(method_exists($deposits, 'links'))
      <div class="p-4 border-t border-slate-100 dark:border-slate-800/80">
        {{ $deposits->links() }}
      </div>
    @endif
  </div>

  {{-- Modal: New Bank Deposit --}}
  <template x-teleport="body">
  <div x-show="createModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div x-show="createModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-950/60 backdrop-blur-md transition-opacity" @click="createModalOpen = false"></div>

    <div class="fixed inset-0 z-10 w-screen overflow-y-auto p-4 sm:p-6 md:p-20 flex min-h-full items-center justify-center pointer-events-none">
      <div x-show="createModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" @click.outside="createModalOpen = false" class="pointer-events-auto relative mx-auto w-full max-w-lg transform rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800 transition-all text-left">

        <form method="POST" action="{{ route('collection.bank-deposits.store') }}">
          @csrf
          <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
            <div class="flex items-center gap-2.5">
              <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                <i class="ph-duotone ph-plus-circle text-xl"></i>
              </div>
              <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white" id="modal-title">
                  Record Bank Deposit
                </h3>
                <p class="text-[11px] text-slate-400">Manual or linked shift remittance</p>
              </div>
            </div>
            <button @click="createModalOpen = false" type="button" class="rounded-lg p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
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
                Linked Closed Cashier Shift (Optional)
              </label>
              <select name="cashier_shift_id" class="block w-full rounded-xl border border-slate-200 bg-white py-2 px-3 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-800 dark:bg-slate-950 dark:text-white">
                <option value="">-- No shift linked (Direct deposit) --</option>
                @foreach($closedShifts ?? [] as $cs)
                  <option value="{{ $cs->id }}">{{ $cs->shift_code }} | {{ $cs->terminal_name }} (₱{{ number_format((float) $cs->actual_cash_counted, 2) }})</option>
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
                <input type="number" step="0.01" min="0" name="cash_amount" class="block w-full rounded-xl border border-slate-200 bg-white py-2 px-3 text-xs font-mono text-right text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-800 dark:bg-slate-950 dark:text-white" value="0.00" required>
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
            <button @click="createModalOpen = false" type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 transition-all">
              Cancel
            </button>
            <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 transition-all">
              <i class="ph ph-check"></i>
              Save Bank Deposit
            </button>
          </div>
        </form>

      </div>
    </div>
  </div>
  </template>

  {{-- Modal: Validate & Clear Bank Deposit --}}
  <template x-teleport="body">
  <div x-show="clearModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div x-show="clearModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-950/60 backdrop-blur-md transition-opacity" @click="clearModalOpen = false"></div>

    <div class="fixed inset-0 z-10 w-screen overflow-y-auto p-4 sm:p-6 md:p-20 flex min-h-full items-center justify-center pointer-events-none">
      <div x-show="clearModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" @click.outside="clearModalOpen = false" class="pointer-events-auto relative mx-auto w-full max-w-lg transform rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800 transition-all text-left">

        <form method="POST" :action="'/collection-management/bank-deposits/' + clearId + '/clear'">
          @csrf
          <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
            <div class="flex items-center gap-2.5">
              <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                <i class="ph-duotone ph-check-circle text-xl"></i>
              </div>
              <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white" id="modal-title">
                  Validate &amp; Clear Bank Deposit
                </h3>
                <p class="text-[11px] text-slate-400">Triggers balanced GL clearance posting</p>
              </div>
            </div>
            <button @click="clearModalOpen = false" type="button" class="rounded-lg p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
              <i class="ph ph-x text-base"></i>
            </button>
          </div>

          <div class="py-4 space-y-4">
            <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
              Clearing deposit <strong class="font-mono text-emerald-600 dark:text-emerald-400" x-text="clearRef"></strong> for <strong class="font-mono text-slate-900 dark:text-white" x-text="'₱' + clearAmount"></strong> will update the hospital's bank balance and trigger balanced GL posting (DR 1020 Cash in Bank / CR 1011 Undeposited Collections).
            </p>

            <div>
              <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                Bank Machine Validation / Reference Code <span class="text-rose-500">*</span>
              </label>
              <input type="text" name="bank_reference_number" class="block w-full rounded-xl border border-slate-200 bg-white py-2 px-3 text-xs font-mono text-slate-900 placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-800 dark:bg-slate-950 dark:text-white" placeholder="e.g. BDO-TRN-9018471" required>
            </div>

            <div>
              <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                Bank Branch / Teller ID (Optional)
              </label>
              <input type="text" name="validated_by_teller" class="block w-full rounded-xl border border-slate-200 bg-white py-2 px-3 text-xs text-slate-900 placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-800 dark:bg-slate-950 dark:text-white" placeholder="e.g. Teller #14 - Makati Main Branch">
            </div>
          </div>

          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
            <button @click="clearModalOpen = false" type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 transition-all">
              Cancel
            </button>
            <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 transition-all">
              <i class="ph ph-bank"></i>
              Post Bank Clearance &amp; GL
            </button>
          </div>
        </form>

      </div>
    </div>
  </div>
  </template>

  {{-- Modal: Reject Deposit --}}
  <template x-teleport="body">
  <div x-show="rejectModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div x-show="rejectModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-950/60 backdrop-blur-md transition-opacity" @click="rejectModalOpen = false"></div>

    <div class="fixed inset-0 z-10 w-screen overflow-y-auto p-4 sm:p-6 md:p-20 flex min-h-full items-center justify-center pointer-events-none">
      <div x-show="rejectModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" @click.outside="rejectModalOpen = false" class="pointer-events-auto relative mx-auto w-full max-w-lg transform rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800 transition-all text-left">

        <form method="POST" :action="'/collection-management/bank-deposits/' + rejectId + '/reject'">
          @csrf
          <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
            <div class="flex items-center gap-2.5">
              <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400">
                <i class="ph-duotone ph-x-circle text-xl"></i>
              </div>
              <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white" id="modal-title">
                  Reject Deposit Slip
                </h3>
                <p class="text-[11px] text-slate-400">Flag discrepancy on remittance</p>
              </div>
            </div>
            <button @click="rejectModalOpen = false" type="button" class="rounded-lg p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
              <i class="ph ph-x text-base"></i>
            </button>
          </div>

          <div class="py-4 space-y-4">
            <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
              Are you sure you want to flag deposit <strong class="font-mono text-rose-600 dark:text-rose-400" x-text="rejectRef"></strong> as rejected or discrepant?
            </p>

            <div>
              <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                Discrepancy / Rejection Reason <span class="text-rose-500">*</span>
              </label>
              <textarea name="reason" rows="3" class="block w-full rounded-xl border border-slate-200 bg-white py-2 px-3 text-xs text-slate-900 placeholder:text-slate-400 focus:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-500/20 dark:border-slate-800 dark:bg-slate-950 dark:text-white" placeholder="Bank teller rejected check, short cash amount..." required></textarea>
            </div>
          </div>

          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
            <button @click="rejectModalOpen = false" type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 transition-all">
              Cancel
            </button>
            <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-rose-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-rose-700 transition-all">
              <i class="ph ph-prohibit"></i>
              Reject Deposit
            </button>
          </div>
        </form>

      </div>
    </div>
  </div>
  </template>

</div>
@endsection

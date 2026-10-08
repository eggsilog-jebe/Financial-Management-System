@extends('layouts.app')

@section('title', 'Batch Deposit Slips - Collection Management | FMS')
@section('module', 'collection')
@section('page', 'deposit-slips')

@section('content')
<div class="space-y-6">
  <!-- Executive Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
    <div>
      <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
          Bank Deposit Slips &amp; Cash Handover
        </h1>
    </div>

    <div class="flex items-center gap-2.5">
      <button 
        type="button" 
        onclick="window.print()" 
        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition-all dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 cursor-pointer"
      >
        <i class="ph-bold ph-printer text-emerald-600"></i>
        <span>Print Manifest</span>
      </button>
      <button 
        type="button" 
        id="btnCreateSlip"
        @click="$dispatch('open-modal', 'createDepositSlipModal')"
        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 ring-1 ring-emerald-600/20 transition-all cursor-pointer"
      >
        <i class="ph-bold ph-plus-circle"></i>
        <span>Create Deposit Slip</span>
      </button>
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
      title="Prepared Deposit Slips" 
      :value="count($deposits ?? [])" 
      :isCurrency="false"
      icon="ph-path" 
      color="slate" 
      subtitle="Generated batch deposit slips"
    />
    <x-stat-card 
      title="Total Vault Deposits" 
      :value="$totalDeposits ?? 0" 
      icon="ph-money" 
      color="emerald" 
      subtitle="Total cash & checks deposited"
    />
    <x-stat-card 
      title="Closed Shifts Pending Remittance" 
      :value="count($closedShifts ?? [])" 
      :isCurrency="false"
      icon="ph-vault" 
      color="amber" 
      subtitle="Turnover awaiting bank deposit"
    />
  </div>


  <!-- Section: Closed Shifts Ready for Custody Handover -->
  <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-800">
      <div>
        <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
          <i class="ph-bold ph-vault text-amber-500"></i> Closed Shifts Ready for Bank Remittance
        </h2>
        <span class="text-xs text-slate-500 dark:text-slate-400">
          Shifts that have been balanced and counted by cashier officers.
        </span>
      </div>
      <span class="inline-flex items-center rounded-xl bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-500/20 dark:bg-amber-950/40 dark:text-amber-300">
        {{ count($closedShifts ?? []) }} Awaiting Deposit
      </span>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
        <thead class="bg-slate-50/75 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:bg-slate-800/50 dark:border-slate-800 dark:text-slate-400">
          <tr>
            <th class="px-4 py-3.5">Shift Code</th>
            <th class="px-4 py-3.5">Terminal</th>
            <th class="px-4 py-3.5">Cashier Officer</th>
            <th class="px-4 py-3.5">Closed Timestamp</th>
            <th class="px-4 py-3.5 text-right">Actual Cash Counted</th>
            <th class="px-4 py-3.5 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
          @forelse($closedShifts ?? [] as $cs)
          <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
            <td class="px-4 py-3 font-mono font-bold text-blue-600 dark:text-blue-400">{{ $cs->shift_code }}</td>
            <td class="px-4 py-3 text-slate-900 dark:text-white font-medium">{{ $cs->terminal_name }}</td>
            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $cs->cashier?->name ?? 'Cashier' }}</td>
            <td class="px-4 py-3 font-mono text-[11px] text-slate-500 dark:text-slate-400">
              {{ $cs->closed_at ? $cs->closed_at->format('M d, Y h:i A') : '-' }}
            </td>
            <td class="px-4 py-3 text-right font-mono font-bold tabular-nums text-emerald-600 dark:text-emerald-400">
              ₱{{ number_format((float) $cs->actual_cash_counted, 2) }}
            </td>
            <td class="px-4 py-3 text-right">
              <button 
                type="button" 
                class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 transition-all cursor-pointer" 
                onclick="prepareDepositForShift('{{ $cs->id }}', '{{ $cs->shift_code }}', '{{ $cs->actual_cash_counted }}')"
              >
                <i class="ph-bold ph-plus-circle"></i>
                <span>Prepare Deposit</span>
              </button>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="6" class="px-4 py-12 text-center text-slate-500 dark:text-slate-400">
              <i class="ph ph-vault text-3xl mb-2 text-slate-400"></i>
              <p>No closed cashier shifts pending deposit.</p>
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <!-- Section: Prepared Batch Deposit Slips -->
  <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-800">
      <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
        <i class="ph-bold ph-receipt text-blue-500"></i> Batch Deposit Slips History
      </h2>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
        <thead class="bg-slate-50/75 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:bg-slate-800/50 dark:border-slate-800 dark:text-slate-400">
          <tr>
            <th class="px-4 py-3.5">Deposit Ref #</th>
            <th class="px-4 py-3.5">Date</th>
            <th class="px-4 py-3.5">Depository Bank</th>
            <th class="px-4 py-3.5">Shift Ref</th>
            <th class="px-4 py-3.5 text-right">Cash Amount (₱)</th>
            <th class="px-4 py-3.5 text-right">Check Amount (₱)</th>
            <th class="px-4 py-3.5 text-right">Total Deposited (₱)</th>
            <th class="px-4 py-3.5 text-center">Status</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
          @forelse($deposits ?? [] as $d)
          @php
            $st = $d->status;
          @endphp
          <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
            <td class="px-4 py-3 font-mono font-bold text-blue-600 dark:text-blue-400">{{ $d->deposit_reference }}</td>
            <td class="px-4 py-3 font-mono text-slate-600 dark:text-slate-300">{{ $d->deposit_date ? $d->deposit_date->format('M d, Y') : '-' }}</td>
            <td class="px-4 py-3">
              <div class="font-semibold text-slate-900 dark:text-white">{{ $d->bankAccount?->bank_name ?? 'Operational Bank' }}</div>
              <span class="text-[11px] text-slate-400 font-mono">{{ $d->bankAccount?->account_number }}</span>
            </td>
            <td class="px-4 py-3 font-mono text-[11px] text-slate-500 dark:text-slate-400">{{ $d->cashierShift?->shift_code ?? 'Manual' }}</td>
            <td class="px-4 py-3 text-right font-mono tabular-nums text-slate-600 dark:text-slate-300">₱{{ number_format((float) $d->cash_amount, 2) }}</td>
            <td class="px-4 py-3 text-right font-mono tabular-nums text-slate-600 dark:text-slate-300">₱{{ number_format((float) $d->check_amount, 2) }}</td>
            <td class="px-4 py-3 text-right font-mono font-bold tabular-nums text-emerald-600 dark:text-emerald-400">₱{{ number_format((float) $d->total_deposited, 2) }}</td>
            <td class="px-4 py-3 text-center">
              @if(in_array($st, ['DEPOSITED', 'CLEARED', 'RECONCILED']))
                <x-status-badge status="active" label="{{ $st }}" size="sm" />
              @elseif($st === 'IN_TRANSIT')
                <x-status-badge status="pending" label="IN TRANSIT" size="sm" />
              @else
                <x-status-badge status="slate" label="{{ $st }}" size="sm" />
              @endif
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="8" class="px-4 py-12 text-center text-slate-500 dark:text-slate-400">
              <i class="ph ph-receipt text-3xl mb-2 text-slate-400"></i>
              <p>No deposit slips generated yet.</p>
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <!-- Pagination Footer -->
    @if(method_exists($deposits, 'links'))
      <div class="border-t border-slate-200 p-4 dark:border-slate-800">
        {{ $deposits->links() }}
      </div>
    @endif
  </div>
</div>

<!-- Modal: Create Deposit Slip -->
<x-modal 
  id="createDepositSlipModal" 
  title="Create Bank Deposit Slip" 
  subtitle="Consolidate cashier cash & checks into an official depository batch slip." 
  icon="ph-plus-circle" 
  iconVariant="blue" 
  size="md" 
  formId="createSlipForm" 
  formAction="{{ route('collection.bank-deposits.store') }}" 
  formMethod="POST" 
  submitText="Save Deposit Slip" 
  submitIcon="ph-check"
>
  <input type="hidden" name="cashier_shift_id" id="slipShiftId">
  <div class="space-y-4">
    <div>
      <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
        Depository Bank Account <span class="text-rose-500">*</span>
      </label>
      <select 
        name="bank_account_id" 
        class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700 shadow-sm focus:border-emerald-600 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
        required
      >
        @foreach($bankAccounts ?? [] as $ba)
          <option value="{{ $ba->id }}">{{ $ba->bank_name }} - {{ $ba->account_name }} ({{ $ba->account_number }})</option>
        @endforeach
      </select>
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
        Deposit Date <span class="text-rose-500">*</span>
      </label>
      <input 
        type="date" 
        name="deposit_date" 
        class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700 shadow-sm focus:border-emerald-600 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
        value="{{ date('Y-m-d') }}" 
        required
      >
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <div>
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          Cash Amount (₱) <span class="text-rose-500">*</span>
        </label>
        <div class="relative">
          <span class="absolute left-3 top-1/2 -translate-y-1/2 font-mono font-bold text-slate-400 text-xs">₱</span>
          <input 
            type="number" 
            step="0.01" 
            min="0" 
            name="cash_amount" 
            id="slipCashAmount" 
            class="w-full rounded-xl border-slate-200 bg-slate-50 py-2 pl-7 pr-3 text-xs font-mono font-bold text-slate-900 shadow-sm focus:border-emerald-600 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-800 dark:text-white" 
            value="0.00" 
            required
          >
        </div>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          Check Amount (₱)
        </label>
        <div class="relative">
          <span class="absolute left-3 top-1/2 -translate-y-1/2 font-mono font-bold text-slate-400 text-xs">₱</span>
          <input 
            type="number" 
            step="0.01" 
            min="0" 
            name="check_amount" 
            id="slipCheckAmount" 
            class="w-full rounded-xl border-slate-200 bg-slate-50 py-2 pl-7 pr-3 text-xs font-mono font-medium text-slate-700 shadow-sm focus:border-emerald-600 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
            value="0.00"
          >
        </div>
      </div>
    </div>
  </div>
</x-modal>

@push('scripts')
<script>
function prepareDepositForShift(shiftId, shiftCode, amount) {
  const shiftInput = document.getElementById('slipShiftId');
  if (shiftInput) shiftInput.value = shiftId;

  const cashInput = document.getElementById('slipCashAmount');
  if (cashInput) cashInput.value = parseFloat(amount).toFixed(2);

  window.dispatchEvent(new CustomEvent('open-modal', { detail: 'createDepositSlipModal' }));
}
</script>
@endpush
@endsection

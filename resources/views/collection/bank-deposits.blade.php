@extends('layouts.app')

@section('title', 'Bank Deposits - Collection Management | FMS')
@section('module', 'collection')
@section('page', 'bank-deposits')

@section('content')
<div class="space-y-6">
  <!-- Executive Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
    <div>
      <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
          Bank Deposits Log &amp; GL Posting
        </h1>
    </div>

    <div class="flex items-center gap-2.5">
      <a 
        href="{{ route('collection.deposit-slips') }}" 
        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition-all dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
      >
        <i class="ph-bold ph-path text-emerald-600"></i>
        <span>Batch Deposit Slips</span>
      </a>
      <button 
        type="button" 
        id="btnCreateDeposit"
        @click="$dispatch('open-modal', 'createDepositModal')"
        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 ring-1 ring-emerald-600/20 transition-all cursor-pointer"
      >
        <i class="ph-bold ph-plus-circle"></i>
        <span>Record Bank Deposit</span>
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
      title="Total Bank Deposits" 
      :value="$totalDeposits ?? 0" 
      icon="ph-bank" 
      color="emerald" 
      subtitle="Cumulative depository deposits"
    />
    <x-stat-card 
      title="Total Deposits Recorded" 
      :value="count($deposits ?? [])" 
      :isCurrency="false"
      icon="ph-receipt" 
      color="blue" 
      subtitle="Deposits logged in ledger"
    />
    @php
      $pendingCnt = ($deposits ?? collect())->whereIn('status', ['PREPARED', 'IN_TRANSIT'])->count();
    @endphp
    <x-stat-card 
      title="Pending Teller Clearance" 
      :value="$pendingCnt" 
      :isCurrency="false"
      icon="ph-clock" 
      color="amber" 
      subtitle="Deposits awaiting bank validation"
    />
  </div>

  

  <!-- Data Table Card -->
  <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-800">
      <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
        <i class="ph-bold ph-bank text-blue-500"></i> Bank Deposit &amp; Clearing Register
      </h2>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
        <thead class="bg-slate-50/75 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:bg-slate-800/50 dark:border-slate-800 dark:text-slate-400">
          <tr>
            <th class="px-4 py-3.5">Deposit Ref #</th>
            <th class="px-4 py-3.5">Deposit Date</th>
            <th class="px-4 py-3.5">Bank Account</th>
            <th class="px-4 py-3.5">Cashier Shift</th>
            <th class="px-4 py-3.5 text-right">Cash / Check</th>
            <th class="px-4 py-3.5 text-right">Total Deposited</th>
            <th class="px-4 py-3.5">Bank Reference / Teller</th>
            <th class="px-4 py-3.5 text-center">Status</th>
            <th class="px-4 py-3.5 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
          @forelse($deposits ?? [] as $d)
          @php
            $st = $d->status;
            $isCleared = in_array($st, ['DEPOSITED', 'CLEARED', 'RECONCILED']);
          @endphp
          <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
            <td class="px-4 py-3 font-mono font-bold text-blue-600 dark:text-blue-400">{{ $d->deposit_reference }}</td>
            <td class="px-4 py-3 font-mono text-slate-600 dark:text-slate-300">{{ $d->deposit_date ? $d->deposit_date->format('M d, Y') : '-' }}</td>
            <td class="px-4 py-3">
              <div class="font-semibold text-slate-900 dark:text-white">{{ $d->bankAccount?->bank_name ?? 'Bank Account' }}</div>
              <span class="text-[11px] text-slate-400 font-mono">{{ $d->bankAccount?->account_number }}</span>
            </td>
            <td class="px-4 py-3 font-mono text-[11px] text-slate-500 dark:text-slate-400">{{ $d->cashierShift?->shift_code ?? '-' }}</td>
            <td class="px-4 py-3 text-right font-mono text-[11px] text-slate-500 dark:text-slate-400 tabular-nums">
              ₱{{ number_format((float) $d->cash_amount, 2) }} / ₱{{ number_format((float) $d->check_amount, 2) }}
            </td>
            <td class="px-4 py-3 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400 tabular-nums">
              ₱{{ number_format((float) $d->total_deposited, 2) }}
            </td>
            <td class="px-4 py-3">
              @if($d->bank_reference_number)
                <span class="font-mono text-xs font-semibold text-slate-900 dark:text-white block">{{ $d->bank_reference_number }}</span>
                <span class="text-[11px] text-slate-400">{{ $d->validated_by_teller }}</span>
              @else
                <span class="text-slate-400 text-[11px] italic">Pending teller validation</span>
              @endif
            </td>
            <td class="px-4 py-3 text-center">
              @if($isCleared)
                <x-status-badge status="active" label="{{ $st }}" size="sm" />
              @elseif($st === 'IN_TRANSIT')
                <x-status-badge status="pending" label="IN TRANSIT" size="sm" />
              @else
                <x-status-badge status="slate" label="{{ $st }}" size="sm" />
              @endif
            </td>
            <td class="px-4 py-3 text-right">
              <div class="flex items-center justify-end gap-1.5">
                @if(! $isCleared)
                  <button 
                    type="button" 
                    class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 transition-all cursor-pointer" 
                    title="Validate & Clear Bank Deposit" 
                    onclick="openClearModal('{{ $d->id }}', '{{ $d->deposit_reference }}', '{{ number_format((float) $d->total_deposited, 2) }}')"
                  >
                    <i class="ph-bold ph-check-circle"></i>
                    <span>Clear</span>
                  </button>
                  <button 
                    type="button" 
                    class="inline-flex items-center rounded-lg border border-rose-200 bg-white px-2 py-1 text-xs font-semibold text-rose-600 shadow-sm hover:bg-rose-50 dark:border-rose-900/50 dark:bg-slate-800 dark:text-rose-400 cursor-pointer" 
                    title="Reject Deposit Slip" 
                    onclick="openRejectModal('{{ $d->id }}', '{{ $d->deposit_reference }}')"
                  >
                    <i class="ph-bold ph-x-circle"></i>
                  </button>
                @else
                  <span class="inline-flex items-center gap-1 rounded-lg bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300">
                    <i class="ph-bold ph-check-fat"></i> Cleared &amp; GL Posted
                  </span>
                @endif
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="9" class="px-4 py-12 text-center text-slate-500 dark:text-slate-400">
              <i class="ph ph-bank text-3xl mb-2 text-slate-400"></i>
              <p>No bank deposits logged.</p>
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

<!-- Modal: New Bank Deposit -->
<x-modal 
  id="createDepositModal" 
  title="Record Bank Deposit" 
  subtitle="Log cash/check remittance from cashier drawers to an accredited hospital depository bank." 
  icon="ph-plus-circle" 
  iconVariant="blue" 
  size="md" 
  formAction="{{ route('collection.bank-deposits.store') }}" 
  formMethod="POST" 
  submitText="Save Bank Deposit" 
  submitIcon="ph-check"
>
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
        Linked Closed Cashier Shift (Optional)
      </label>
      <select 
        name="cashier_shift_id" 
        class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700 shadow-sm focus:border-emerald-600 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
      >
        <option value="">-- No shift linked (Direct deposit) --</option>
        @foreach($closedShifts ?? [] as $cs)
          <option value="{{ $cs->id }}">{{ $cs->shift_code }} | {{ $cs->terminal_name }} (₱{{ number_format((float) $cs->actual_cash_counted, 2) }})</option>
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
            class="w-full rounded-xl border-slate-200 bg-slate-50 py-2 pl-7 pr-3 text-xs font-mono font-medium text-slate-700 shadow-sm focus:border-emerald-600 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
            value="0.00"
          >
        </div>
      </div>
    </div>
  </div>
</x-modal>

<!-- Modal: Validate & Clear Bank Deposit -->
<x-modal 
  id="clearDepositModal" 
  title="Validate & Clear Bank Deposit" 
  subtitle="Post confirmed bank transaction and trigger balanced GL posting (DR 1020 / CR 1011)." 
  icon="ph-check-circle" 
  iconVariant="emerald" 
  size="md" 
  formId="clearForm" 
  formAction="" 
  formMethod="POST" 
  submitText="Post Bank Clearance & GL" 
  submitIcon="ph-bank"
>
  <div class="space-y-4">
    <div class="rounded-xl bg-slate-50 p-3 ring-1 ring-slate-200/60 dark:bg-slate-800/40 dark:ring-slate-800 space-y-1.5">
      <div class="flex justify-between items-center text-xs">
        <span class="text-slate-400">Deposit Slip:</span>
        <span id="clearDepositRef" class="font-mono font-bold text-blue-600 dark:text-blue-400">-</span>
      </div>
      <div class="flex justify-between items-center text-xs pt-1 border-t border-slate-200 dark:border-slate-700">
        <span class="text-slate-400">Total Validated:</span>
        <span id="clearDepositAmount" class="font-mono font-bold text-emerald-600 dark:text-emerald-400 tabular-nums">-</span>
      </div>
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
        Bank Machine Validation / Reference Code <span class="text-rose-500">*</span>
      </label>
      <input 
        type="text" 
        name="bank_reference_number" 
        class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs font-mono font-medium text-slate-700 shadow-sm focus:border-emerald-500 focus:bg-white focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
        placeholder="e.g. BDO-TRN-9018471" 
        required
      >
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
        Bank Branch / Teller ID (Optional)
      </label>
      <input 
        type="text" 
        name="validated_by_teller" 
        class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700 placeholder-slate-400 shadow-sm focus:border-emerald-500 focus:bg-white focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
        placeholder="e.g. Teller #14 - Makati Main Branch"
      >
    </div>
  </div>
</x-modal>

<!-- Modal: Reject Deposit -->
<x-modal 
  id="rejectDepositModal" 
  title="Reject Deposit Slip" 
  subtitle="Flag deposit slip as rejected or discrepant due to bank teller validation failure." 
  icon="ph-x-circle" 
  iconVariant="rose" 
  size="md" 
  formId="rejectForm" 
  formAction="" 
  formMethod="POST" 
  submitText="Reject Deposit" 
  submitIcon="ph-prohibit"
>
  <div class="space-y-4">
    <p class="text-xs text-slate-500 dark:text-slate-400">
      Are you sure you want to flag deposit <strong id="rejectDepositRef" class="font-mono text-rose-600"></strong> as rejected/discrepant?
    </p>

    <div>
      <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
        Discrepancy / Rejection Reason <span class="text-rose-500">*</span>
      </label>
      <textarea 
        name="reason" 
        rows="3" 
        class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700 placeholder-slate-400 shadow-sm focus:border-rose-500 focus:bg-white focus:ring-rose-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
        placeholder="Bank teller rejected check, short cash amount..." 
        required
      ></textarea>
    </div>
  </div>
</x-modal>
@endsection

@push('scripts')
<script>
function openClearModal(id, ref, amount) {
  const form = document.getElementById('clearForm');
  if (form) {
    form.action = "{{ url('/collection-management/bank-deposits') }}/" + id + "/clear";
  }
  const elRef = document.getElementById('clearDepositRef');
  if (elRef) elRef.textContent = ref;
  const elAmt = document.getElementById('clearDepositAmount');
  if (elAmt) elAmt.textContent = '₱' + amount;

  window.dispatchEvent(new CustomEvent('open-modal', { detail: 'clearDepositModal' }));
}

function openRejectModal(id, ref) {
  const form = document.getElementById('rejectForm');
  if (form) {
    form.action = "{{ url('/collection-management/bank-deposits') }}/" + id + "/reject";
  }
  const elRef = document.getElementById('rejectDepositRef');
  if (elRef) elRef.textContent = ref;

  window.dispatchEvent(new CustomEvent('open-modal', { detail: 'rejectDepositModal' }));
}
</script>
@endpush

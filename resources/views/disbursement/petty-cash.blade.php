@extends('layouts.app')

@section('title', 'Petty Cash Custody & Replenishment - Disbursement | FMS')
@section('module', 'disbursement')
@section('page', 'petty-cash')

@section('content')
<div class="space-y-6">
  <!-- Executive Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
    <div>
      <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
          Petty Cash Custody &amp; Replenishment
        </h1>
    </div>

    <div class="flex flex-wrap items-center gap-2.5">
      @if($funds->isNotEmpty())
        <!-- Fund Selector Dropdown -->
        <div class="flex items-center gap-2">
          <label class="text-xs font-semibold text-slate-500 dark:text-slate-400 whitespace-nowrap flex items-center gap-1">
            <i class="ph-bold ph-vault text-amber-500"></i> Active:
          </label>
          <select 
            class="rounded-xl border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm focus:border-amber-500 focus:bg-white focus:ring-amber-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
            onchange="window.location.href='{{ route('disbursement.petty-cash') }}?fund_id=' + this.value"
          >
            @foreach($funds as $f)
              <option value="{{ $f->id }}" {{ $fund && $fund->id === $f->id ? 'selected' : '' }}>
                {{ $f->fund_name }} ({{ $f->custodian_name }})
              </option>
            @endforeach
          </select>
        </div>
      @endif

      <button 
        type="button" 
        @click="$dispatch('open-modal', 'createFundModal')"
        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition-all dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 cursor-pointer"
      >
        <i class="ph-bold ph-plus-circle text-amber-600"></i>
        <span>New Fund</span>
      </button>

      @if($fund)
        <button 
          type="button" 
          @click="$dispatch('open-modal', 'replenishFundModal')"
          class="inline-flex items-center gap-1.5 rounded-xl border border-amber-300 bg-amber-50 px-3.5 py-2 text-xs font-semibold text-amber-800 shadow-sm hover:bg-amber-100 transition-all dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-300 dark:hover:bg-amber-900/40 cursor-pointer"
        >
          <i class="ph-bold ph-arrows-clockwise"></i>
          <span>Replenish Float</span>
        </button>
        <button 
          type="button" 
          @click="$dispatch('open-modal', 'createExpenseModal')"
          class="inline-flex items-center gap-1.5 rounded-xl bg-amber-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-amber-700 ring-1 ring-amber-600/20 transition-all cursor-pointer"
        >
          <i class="ph-bold ph-plus"></i>
          <span>Record Expense Slip</span>
        </button>
      @endif
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

  @if($fund)
    <!-- Metric Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
      <x-stat-card 
        title="Revolving Float Limit" 
        :value="$fund->float_limit ?? 0" 
        icon="ph-wallet" 
        color="slate" 
        subtitle="Maximum authorized cash balance"
      />
      <x-stat-card 
        title="Current Cash on Hand" 
        :value="$fund->current_balance ?? 0" 
        icon="ph-money" 
        color="emerald" 
        subtitle="Verified physical drawer cash"
      />
      <x-stat-card 
        title="Unreplenished Expense Slips" 
        :value="$unreplenishedTotal ?? 0" 
        icon="ph-receipt" 
        color="amber" 
        subtitle="Receipts awaiting replenishment"
      />
      <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800 flex flex-col justify-between">
        <div>
          <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider block mb-1">Custodian in Charge</span>
          <h4 class="text-base font-bold text-slate-900 dark:text-white">{{ $fund->custodian_name }}</h4>
        </div>
        <div class="text-[11px] text-slate-400 font-mono mt-3 flex items-center gap-1">
          <i class="ph-bold ph-shield-check text-emerald-500"></i> GL Account: 1030
        </div>
      </div>
    </div>


    <!-- Expense Slips Table -->
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-800">
        <div>
          <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
            <i class="ph-bold ph-receipt text-amber-500"></i> Expense Log &mdash; {{ $fund->fund_name }}
          </h2>
          <span class="text-xs text-slate-500 dark:text-slate-400">
            Emergency vouchers disbursed from this revolving custody drawer.
          </span>
        </div>
        <span class="inline-flex items-center rounded-xl bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-500/20 dark:bg-amber-950/40 dark:text-amber-300">
          {{ $expenses->total() }} Logged Vouchers
        </span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
          <thead class="bg-slate-50/75 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:bg-slate-800/50 dark:border-slate-800 dark:text-slate-400">
            <tr>
              <th class="px-4 py-3.5">Voucher #</th>
              <th class="px-4 py-3.5">Payee &amp; Department</th>
              <th class="px-4 py-3.5">Date</th>
              <th class="px-4 py-3.5">Particulars</th>
              <th class="px-4 py-3.5">Receipt Ref</th>
              <th class="px-4 py-3.5 text-right">Amount (₱)</th>
              <th class="px-4 py-3.5 text-center">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
            @forelse($expenses as $e)
            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
              <td class="px-4 py-3 font-mono font-bold text-amber-600 dark:text-amber-400">
                {{ $e->voucher_number }}
              </td>
              <td class="px-4 py-3">
                <div class="font-semibold text-slate-900 dark:text-white">{{ $e->payee }}</div>
                <div class="text-[11px] text-slate-400">{{ $e->department }}</div>
              </td>
              <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                {{ $e->expense_date ? $e->expense_date->format('M d, Y') : '—' }}
              </td>
              <td class="px-4 py-3 text-slate-700 dark:text-slate-300">
                {{ $e->particulars }}
              </td>
              <td class="px-4 py-3 font-mono text-[11px] text-slate-500 dark:text-slate-400">
                {{ $e->receipt_ref ?? '—' }}
              </td>
              <td class="px-4 py-3 text-right font-mono font-bold tabular-nums text-slate-900 dark:text-white">
                ₱{{ number_format((float) $e->amount, 2) }}
              </td>
              <td class="px-4 py-3 text-center">
                @if($e->status === 'REPLENISHED')
                  <x-status-badge status="active" label="REPLENISHED" size="sm" />
                @elseif($e->status === 'VOIDED')
                  <x-status-badge status="inactive" label="VOIDED" size="sm" />
                @else
                  <x-status-badge status="pending" label="{{ $e->status }}" size="sm" />
                @endif
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="7" class="px-4 py-12 text-center text-slate-500 dark:text-slate-400">
                <i class="ph ph-receipt text-3xl mb-2 text-slate-400"></i>
                <p>No petty cash expense slips logged yet for this fund.</p>
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <!-- Pagination & Meta -->
      <div class="flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-slate-200 px-4 py-3 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
        <span>Showing {{ $expenses->firstItem() ?? 0 }} - {{ $expenses->lastItem() ?? 0 }} of {{ $expenses->total() }} Slips</span>
        <div>
          {{ $expenses->links() }}
        </div>
      </div>
    </div>
  @else
    <!-- Zero State -->
    <div class="rounded-2xl bg-white p-12 text-center shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
      <div class="mb-4 inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-950/40 dark:text-amber-300">
        <i class="ph-bold ph-vault text-3xl"></i>
      </div>
      <h3 class="text-base font-bold text-slate-900 dark:text-white">No Petty Cash Revolving Funds Registered</h3>
      <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto">
        Set up a departmental or operational revolving fund with a designated custodian and float limit to start logging petty disbursements.
      </p>
      <div class="mt-6">
        <button 
          type="button" 
          @click="$dispatch('open-modal', 'createFundModal')"
          class="inline-flex items-center gap-1.5 rounded-xl bg-amber-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-amber-700 ring-1 ring-amber-600/20 transition-all cursor-pointer"
        >
          <i class="ph-bold ph-plus-circle"></i>
          <span>Register New Petty Cash Fund</span>
        </button>
      </div>
    </div>
  @endif
</div>

<!-- Modal: Create Petty Cash Fund -->
<x-modal 
  id="createFundModal" 
  title="Register Petty Cash Fund" 
  subtitle="Establish a new revolving emergency cash drawer and assign a designated custodian." 
  icon="ph-vault" 
  iconVariant="amber" 
  size="md" 
  formAction="{{ route('disbursement.petty-cash.funds.store') }}" 
  formMethod="POST" 
  submitText="Create Fund" 
  submitIcon="ph-check"
>
  <div class="space-y-4">
    <div>
      <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
        Fund Name <span class="text-rose-500">*</span>
      </label>
      <input 
        type="text" 
        name="fund_name" 
        class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700 placeholder-slate-400 shadow-sm focus:border-amber-500 focus:bg-white focus:ring-amber-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
        placeholder="e.g. Main Hospital Operating Petty Cash, ER Petty Cash" 
        required
      >
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
        Designated Custodian Legal Name <span class="text-rose-500">*</span>
      </label>
      <input 
        type="text" 
        name="custodian_name" 
        class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700 placeholder-slate-400 shadow-sm focus:border-amber-500 focus:bg-white focus:ring-amber-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
        placeholder="e.g. Maria Santos (Chief Cashier)" 
        required
      >
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <div>
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          Float Limit (₱) <span class="text-rose-500">*</span>
        </label>
        <div class="relative">
          <span class="absolute left-3 top-1/2 -translate-y-1/2 font-mono font-bold text-slate-400 text-xs">₱</span>
          <input 
            type="number" 
            step="0.01" 
            name="float_limit" 
            class="w-full rounded-xl border-slate-200 bg-slate-50 py-2 pl-7 pr-3 text-xs font-mono font-bold text-slate-900 shadow-sm focus:border-amber-500 focus:bg-white focus:ring-amber-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white" 
            placeholder="50000.00" 
            required
          >
        </div>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">GL Account Code</label>
        <input 
          type="text" 
          name="gl_code" 
          class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs font-mono font-medium text-slate-700 shadow-sm focus:border-amber-500 focus:bg-white focus:ring-amber-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
          placeholder="1030" 
          value="1030"
        >
      </div>
    </div>
  </div>
</x-modal>

@if($fund)
<!-- Modal: Record Expense Slip -->
<x-modal 
  id="createExpenseModal" 
  title="Record Expense Slip &mdash; {{ $fund->fund_name }}" 
  subtitle="Log cash payout with official receipt or expense justification." 
  icon="ph-receipt" 
  iconVariant="amber" 
  size="md" 
  formAction="{{ route('disbursement.petty-cash.expense') }}" 
  formMethod="POST" 
  submitText="Save Expense Slip" 
  submitIcon="ph-check"
>
  <input type="hidden" name="petty_cash_fund_id" value="{{ $fund->id }}">
  <div class="space-y-4">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <div>
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          Payee Name <span class="text-rose-500">*</span>
        </label>
        <input 
          type="text" 
          name="payee" 
          class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700 placeholder-slate-400 shadow-sm focus:border-amber-500 focus:bg-white focus:ring-amber-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
          placeholder="e.g. Courier Service" 
          required
        >
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          Department <span class="text-rose-500">*</span>
        </label>
        <input 
          type="text" 
          name="department" 
          class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700 placeholder-slate-400 shadow-sm focus:border-amber-500 focus:bg-white focus:ring-amber-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
          placeholder="e.g. ER, Pharmacy" 
          required
        >
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <div>
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          Expense Date <span class="text-rose-500">*</span>
        </label>
        <input 
          type="date" 
          name="expense_date" 
          class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700 shadow-sm focus:border-amber-500 focus:bg-white focus:ring-amber-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
          value="{{ date('Y-m-d') }}" 
          required
        >
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          Expense Amount (₱) <span class="text-rose-500">*</span>
        </label>
        <div class="relative">
          <span class="absolute left-3 top-1/2 -translate-y-1/2 font-mono font-bold text-slate-400 text-xs">₱</span>
          <input 
            type="number" 
            step="0.01" 
            name="amount" 
            class="w-full rounded-xl border-slate-200 bg-slate-50 py-2 pl-7 pr-3 text-xs font-mono font-bold text-slate-900 shadow-sm focus:border-amber-500 focus:bg-white focus:ring-amber-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white" 
            placeholder="0.00" 
            max="{{ $fund->current_balance }}" 
            required
          >
        </div>
        <span class="text-[11px] text-slate-400 mt-1 block">Max available: ₱{{ number_format((float) $fund->current_balance, 2) }}</span>
      </div>
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
        Particulars / Purpose <span class="text-rose-500">*</span>
      </label>
      <input 
        type="text" 
        name="particulars" 
        class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700 placeholder-slate-400 shadow-sm focus:border-amber-500 focus:bg-white focus:ring-amber-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
        placeholder="e.g. Urgent specimen dispatch / office hardware" 
        required
      >
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
        Official Receipt / Invoice Ref #
      </label>
      <input 
        type="text" 
        name="receipt_ref" 
        class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs font-mono text-slate-700 placeholder-slate-400 shadow-sm focus:border-amber-500 focus:bg-white focus:ring-amber-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
        placeholder="e.g. OR-88190"
      >
    </div>
  </div>
</x-modal>

<!-- Modal: Replenish Revolving Fund -->
<x-modal 
  id="replenishFundModal" 
  title="Replenish Petty Cash Revolving Fund" 
  subtitle="Authorizes reimbursement check to restore the revolving float balance." 
  icon="ph-arrows-clockwise" 
  iconVariant="amber" 
  size="md" 
  formAction="{{ route('disbursement.petty-cash.replenish') }}" 
  formMethod="POST" 
  submitText="Authorize Replenishment" 
  submitIcon="ph-check"
>
  <input type="hidden" name="fund_id" value="{{ $fund->id }}">
  <div class="space-y-4">
    <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200/60 dark:bg-slate-800/40 dark:ring-slate-800 space-y-2">
      <div class="flex justify-between items-center text-xs">
        <span class="text-slate-500">Unreplenished Expense Slips:</span>
        <span class="font-mono font-bold text-rose-600 dark:text-rose-400 text-sm tabular-nums">₱{{ number_format((float) $unreplenishedTotal, 2) }}</span>
      </div>
      <div class="flex justify-between items-center text-xs pt-1 border-t border-slate-200 dark:border-slate-700">
        <span class="text-slate-500">Target Float Restoration:</span>
        <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400 text-sm tabular-nums">₱{{ number_format((float) $fund->float_limit, 2) }}</span>
      </div>
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
        Disbursing Bank Account <span class="text-rose-500">*</span>
      </label>
      <select 
        name="bank_account_id" 
        class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700 shadow-sm focus:border-amber-500 focus:bg-white focus:ring-amber-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" 
        required
      >
        <option value="">-- Select Bank Account --</option>
        @foreach($bankAccounts as $b)
          <option value="{{ $b->id }}">{{ $b->bank_name }} ({{ $b->account_number }}) - Bal: ₱{{ number_format((float) $b->balance, 2) }}</option>
        @endforeach
      </select>
      <span class="text-[11px] text-slate-400 mt-1 block">A reimbursement disbursement voucher and balanced General Ledger entry will be generated automatically.</span>
    </div>
  </div>
</x-modal>
@endif
@endsection
